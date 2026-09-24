<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Production;
use App\Models\Guide;
use App\Models\Color;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
{
    /**
     * Obtiene las métricas y datos clave para el Dashboard.
     */
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        // Consultas base de sumatorias
        $pendingQuery = Invoice::where('payment_status', 'PENDIENTE');
        $incomeQuery = Invoice::where('payment_status', 'PAGADA');
        $productionQuery = Production::query();

        // Consultas para contar totales generales
        $guidesQuery = Guide::query();
        $invoicesQuery = Invoice::query();
        $colorsQuery = Color::query();
        $purchaseOrdersQuery = PurchaseOrder::query();

        $criticalQuery = Invoice::with('production')->where('payment_status', 'PENDIENTE');
        $latestProdQuery = Production::with(['user', 'purchaseOrder']);

        // Aplicar filtro de rango de fechas si existe
        if ($dateFrom && $dateTo) {
            // Facturas filtradas por fecha de emisión (jueves, cuando se sube la factura)
            $pendingQuery->whereBetween('issue_date', [$dateFrom, $dateTo]);
            $incomeQuery->whereBetween('issue_date', [$dateFrom, $dateTo]);
            $invoicesQuery->whereBetween('issue_date', [$dateFrom, $dateTo]);
            $criticalQuery->whereBetween('issue_date', [$dateFrom, $dateTo]);

            // Producción filtrada por fecha de creación (martes, cuando se registra)
            $productionQuery->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
            $latestProdQuery->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

            // Guías y otros módulos por su fecha correspondiente
            $guidesQuery->whereBetween('issue_date', [$dateFrom, $dateTo]);
            $colorsQuery->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
            $purchaseOrdersQuery->whereBetween('issue_date', [$dateFrom, $dateTo]);

        } elseif ($dateFrom) {
            // Si solo hay fecha de inicio, filtrar desde esa fecha en adelante
            $pendingQuery->where('issue_date', '>=', $dateFrom);
            $incomeQuery->where('issue_date', '>=', $dateFrom);
            $invoicesQuery->where('issue_date', '>=', $dateFrom);
            $criticalQuery->where('issue_date', '>=', $dateFrom);
            $productionQuery->where('created_at', '>=', $dateFrom);
            $latestProdQuery->where('created_at', '>=', $dateFrom);
            $guidesQuery->where('issue_date', '>=', $dateFrom);
            $colorsQuery->where('created_at', '>=', $dateFrom);
            $purchaseOrdersQuery->where('issue_date', '>=', $dateFrom);
        }

        // 1. Totales Financieros y Producción
        $pendingInvoicing = (float) $pendingQuery->sum('total_amount');
        $monthlyIncome = (float) $incomeQuery->sum('total_amount');
        $monthlyProduction = (int) $productionQuery->sum('quantity');

        // 2. Totales Generales (Para tarjetas)
        $totalGuides = $guidesQuery->count();
        $totalInvoices = $invoicesQuery->count();
        $totalColors = $colorsQuery->count();
        $totalPurchaseOrders = $purchaseOrdersQuery->count();

        // 3. Datos para Gráfico de Dona (Estado de Facturas)
        $invoiceStatusData = (clone $invoicesQuery)
            ->selectRaw('payment_status, count(*) as count')
            ->groupBy('payment_status')
            ->get();

        $donutChart = ['pagadas' => 0, 'pendientes' => 0];
        foreach ($invoiceStatusData as $status) {
            if ($status->payment_status === 'PAGADA') {
                $donutChart['pagadas'] = (int) $status->count;
            } elseif ($status->payment_status === 'PENDIENTE') {
                $donutChart['pendientes'] = (int) $status->count;
            }
        }

        // 4. Datos para Gráfico de Barras (Tendencia de Producción)
        $barChartQuery = Production::selectRaw('DATE(created_at) as date, sum(quantity) as total');
        if ($dateFrom && $dateTo) {
            $barChartQuery->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
        } elseif ($dateFrom) {
            $barChartQuery->where('created_at', '>=', $dateFrom);
        }
        $productionTrend = $barChartQuery->groupBy('date')->orderBy('date', 'asc')->get();

        // 5. Tendencia de Ingresos (Gráfico de Líneas)
        $incomeTrendQuery = Invoice::query();

        if ($dateFrom && $dateTo) {
            // Vista diaria dentro del rango seleccionado
            $incomeTrendData = $incomeTrendQuery
                ->whereBetween('issue_date', [$dateFrom, $dateTo])
                ->selectRaw('DATE(issue_date) as label, payment_status, sum(total_amount) as total')
                ->groupBy('label', 'payment_status')
                ->orderBy('label', 'asc')
                ->get();
        } else {
            // Vista mensual del año actual (sin filtro)
            $currentYear = Carbon::now()->year;
            $incomeTrendData = $incomeTrendQuery
                ->whereYear('issue_date', $currentYear)
                ->selectRaw('EXTRACT(MONTH FROM issue_date) as label, payment_status, sum(total_amount) as total')
                ->groupBy('label', 'payment_status')
                ->orderBy('label', 'asc')
                ->get();
        }

        // 6. Línea de Tiempo (Timeline) - Últimos 5 eventos
        $latestGuides = Guide::orderBy('created_at', 'desc')->take(5)->get()->map(function ($g) {
            return [
                'type' => 'guide',
                'title' => 'Guía Registrada',
                'description' => 'Se registró la guía ' . $g->guide_number,
                'created_at' => $g->created_at,
            ];
        });

        $latestInvoices = Invoice::orderBy('created_at', 'desc')->take(5)->get()->map(function ($i) {
            return [
                'type' => 'invoice',
                'title' => 'Factura Registrada',
                'description' => 'Se registró la factura ' . $i->invoice_number,
                'created_at' => $i->created_at,
            ];
        });

        $latestProductions = Production::orderBy('created_at', 'desc')->take(5)->get()->map(function ($p) {
            return [
                'type' => 'production',
                'title' => 'Producción Registrada',
                'description' => 'Se registró la O/P ' . $p->production_order_number,
                'created_at' => $p->created_at,
            ];
        });

        $latestPurchaseOrders = PurchaseOrder::orderBy('created_at', 'desc')->take(5)->get()->map(function ($po) {
            return [
                'type' => 'purchase_order',
                'title' => 'O/C Registrada',
                'description' => 'Se registró la O/C ' . $po->purchase_order_number,
                'created_at' => $po->created_at,
            ];
        });

        // Combinar, ordenar y tomar solo los últimos 5 absolutos
        $timeline = collect($latestGuides)
            ->merge($latestInvoices)
            ->merge($latestProductions)
            ->merge($latestPurchaseOrders)
            ->sortByDesc('created_at')
            ->take(5)
            ->values()
            ->all();

        // 7. Consultas para las tablas del dashboard
        $criticalInvoices = $criticalQuery->orderBy('issue_date', 'desc')->take(5)->get();
        $latestProductions = $latestProdQuery->orderBy('created_at', 'desc')->take(5)->get();

        return response()->json([
            'success' => true,
            'message' => 'Datos del dashboard obtenidos correctamente.',
            'data' => [
                // Métricas anteriores
                'pending_invoicing' => $pendingInvoicing,
                'monthly_income' => $monthlyIncome,
                'monthly_production' => $monthlyProduction,
                
                // Nuevas Métricas
                'total_guides' => $totalGuides,
                'total_invoices' => $totalInvoices,
                'total_colors' => $totalColors,
                'total_purchase_orders' => $totalPurchaseOrders,
                
                // Gráficos y Timeline
                'donut_chart' => $donutChart,
                'bar_chart' => $productionTrend,
                'income_trend' => $incomeTrendData,
                'timeline' => $timeline,

                // Tablas
                'critical_invoices' => $criticalInvoices,
                'latest_productions' => $latestProductions,
            ]
        ], 200);
    }
}
