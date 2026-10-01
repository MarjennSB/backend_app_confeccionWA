<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ROLES
        $role1 = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'AD']);
        $role2 = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'GT']);
        $role3 = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'OP']);
        $role4 = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'CT']);

        // PERMISOS - ROLES
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_rol', 'description' => 'Lista de Roles'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_rol', 'description' => 'Registrar Roles'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_rol', 'description' => 'Editar Roles'])->syncRoles([$role1]);

        // PERMISOS - USUARIOS
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_usuario', 'description' => 'Lista de Usuarios'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_usuario', 'description' => 'Registrar Usuarios'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_usuario', 'description' => 'Editar Usuarios'])->syncRoles([$role1]);

        // PERMISOS - COLOR
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_color', 'description' => 'Lista de Colores'])->syncRoles([$role1, $role3]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_color', 'description' => 'Registrar Colores'])->syncRoles([$role1, $role3]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_color', 'description' => 'Editar Colores'])->syncRoles([$role1, $role3]);

        //PERMISOS - ORDEN SERVICIO
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_orden_servicio', 'description' => 'Lista de Ordenes de Servicio'])->syncRoles([$role1, $role3]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_orden_servicio', 'description' => 'Registrar Ordenes de Servicio'])->syncRoles([$role1, $role3]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_orden_servicio', 'description' => 'Editar Ordenes de Servicio'])->syncRoles([$role1, $role3]);
        
        //PERMISOS - GUIA
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_guia', 'description' => 'Lista de Guia'])->syncRoles([$role1, $role3]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_guia', 'description' => 'Registrar Guia'])->syncRoles([$role1, $role3]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_guia', 'description' => 'Editar Guia'])->syncRoles([$role1, $role3]);

        //PERMISOS - PRODUCCION
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_produccion', 'description' => 'Lista de Produccion'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_produccion', 'description' => 'Registrar Produccion'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_produccion', 'description' => 'Editar Produccion'])->syncRoles([$role1]);

        //PERMISOS - FACTURA
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_factura', 'description' => 'Lista de Factura'])->syncRoles([$role1, $role4]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_factura', 'description' => 'Registrar Factura'])->syncRoles([$role1, $role4]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_factura', 'description' => 'Editar Factura'])->syncRoles([$role1, $role4]);
        
        //PERMISOS - DASHBOARD
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'dashboard', 'description' => 'Dashboard'])->syncRoles([$role1, $role2]);

        // CREAR USUARIO ADMINISTRADOR POR DEFECTO
        $adminUser = User::firstOrCreate(
            ['email' => 'confeccioneswa@gmail.com'],
            [
                'name' => 'Administrador',
                'document_type_id' => 1,
                'document_number' => '76122794',
                'first_name' => 'Marcos',
                'last_name_father' => 'Atanacio',
                'last_name_mother' => 'Acevedo',
                'gender_id' => 1,
                'password' => Hash::make('confeccioneswa'),
                'is_active' => true
            ]
        );
        $adminUser->assignRole($role1);

        // CREAR USUARIO GERENTE (GT)
        $gerenteUser = User::firstOrCreate(
            ['email' => 'gerente@gmail.com'],
            [
                'name' => 'Gerente',
                'document_type_id' => 1,
                'document_number' => '21872258',
                'first_name' => 'Gerente',
                'last_name_father' => 'Test',
                'last_name_mother' => 'Test',
                'gender_id' => 1,
                'password' => Hash::make('123456'),
                'is_active' => true
            ]
        );
        $gerenteUser->assignRole($role2);

        // CREAR USUARIO OPERARIO (OP)
        $operarioUser = User::firstOrCreate(
            ['email' => 'operario@gmail.com'],
            [
                'name' => 'Operario',
                'document_type_id' => 1,
                'document_number' => '21895824',
                'first_name' => 'Operario',
                'last_name_father' => 'Test',
                'last_name_mother' => 'Test',
                'gender_id' => 1,
                'password' => Hash::make('123456'),
                'is_active' => true
            ]
        );
        $operarioUser->assignRole($role3);

        // CREAR USUARIO CONTADOR (CT)
        $contadorUser = User::firstOrCreate(
            ['email' => 'contador@gmail.com'],
            [
                'name' => 'Contador',
                'document_type_id' => 1,
                'document_number' => '45856933',
                'first_name' => 'Contador',
                'last_name_father' => 'Test',
                'last_name_mother' => 'Test',
                'gender_id' => 1,
                'password' => Hash::make('123456'),
                'is_active' => true
            ]
        );
        $contadorUser->assignRole($role4);
    }
};