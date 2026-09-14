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
        $role1 = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'ADMINISTRADOR']);
        $role2 = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'USUARIO EXTERNO']);

        // PERMISOS - ROLES
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_rol', 'description' => 'Lista de Roles'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_rol', 'description' => 'Registrar Roles'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_rol', 'description' => 'Editar Roles'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'eliminar_rol', 'description' => 'Eliminar Roles'])->syncRoles([$role1]);

        // PERMISOS - USUARIOS
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_usuario', 'description' => 'Lista de Usuarios'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_usuario', 'description' => 'Registrar Usuarios'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_usuario', 'description' => 'Editar Usuarios'])->syncRoles([$role1]);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'eliminar_usuario', 'description' => 'Eliminar Usuarios'])->syncRoles([$role1]);

        // CREAR USUARIO ADMINISTRADOR POR DEFECTO
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@correo.com'],
            [
                'name' => 'Administrador',
                'document_type_id' => 1,
                'document_number' => '12345678',
                'first_name' => 'test',
                'last_name_father' => 'test',
                'last_name_mother' => 'test',
                'gender_id' => 1,
                'password' => Hash::make('123456'),
                'is_active' => true
            ]
        );
        $adminUser->assignRole($role1);
    }
};