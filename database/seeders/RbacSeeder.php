<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Operatore;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permessi
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'operatore';

        // ═══════════════════════════════════════
        // RUOLI
        // ═══════════════════════════════════════
        $roles = ['superadmin', 'admin', 'owner', 'owner_readonly', 'prestampa', 'operatore', 'spedizione', 'fiery_contatori', 'viewer'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => $guard]);
        }

        // ═══════════════════════════════════════
        // PERMESSI
        // ═══════════════════════════════════════
        $permissions = [
            // Dashboard
            'view-dashboard-owner',
            'view-dashboard-operatore',
            'view-dashboard-spedizione',
            'view-dashboard-prestampa',
            'view-dashboard-admin',
            'view-kiosk',
            // Ordini e fasi
            'edit-ordine',
            'edit-fase',
            'delete-fase',
            'edit-stato',
            // Report
            'view-report',
            'view-report-ore',
            'view-report-direzione',
            'view-audit-log',
            // Gestione
            'manage-users',
            'manage-turni',
            'manage-ean',
            'manage-tariffe',
            // Azioni
            'sync-onda',
            'export-data',
            'edit-note-consegne',
            'edit-note-fustelle',
            'edit-prestampa',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        // ═══════════════════════════════════════
        // ASSEGNAZIONE PERMESSI AI RUOLI
        // ═══════════════════════════════════════

        // Superadmin: tutto
        Role::findByName('superadmin', $guard)->syncPermissions(Permission::where('guard_name', $guard)->get());

        // Admin: tutto tranne kiosk
        Role::findByName('admin', $guard)->syncPermissions([
            'view-dashboard-admin', 'view-dashboard-owner', 'view-report', 'view-report-ore',
            'view-report-direzione', 'view-audit-log', 'manage-users', 'manage-turni',
            'manage-ean', 'manage-tariffe', 'edit-ordine', 'edit-fase', 'delete-fase',
            'edit-stato', 'sync-onda', 'export-data', 'edit-note-consegne',
        ]);

        // Owner: dashboard owner + report + azioni
        Role::findByName('owner', $guard)->syncPermissions([
            'view-dashboard-owner', 'view-report', 'view-report-ore', 'view-report-direzione',
            'view-audit-log', 'edit-ordine', 'edit-fase', 'delete-fase', 'edit-stato',
            'sync-onda', 'export-data', 'edit-note-consegne', 'edit-note-fustelle',
        ]);

        // Owner readonly: solo lettura
        Role::findByName('owner_readonly', $guard)->syncPermissions([
            'view-dashboard-owner', 'view-report', 'view-report-ore', 'view-report-direzione',
            'view-audit-log', 'export-data',
        ]);

        // Prestampa
        Role::findByName('prestampa', $guard)->syncPermissions([
            'view-dashboard-prestampa', 'view-dashboard-operatore', 'edit-prestampa',
            'edit-note-fustelle',
        ]);

        // Operatore
        Role::findByName('operatore', $guard)->syncPermissions([
            'view-dashboard-operatore', 'edit-fase',
        ]);

        // Spedizione
        Role::findByName('spedizione', $guard)->syncPermissions([
            'view-dashboard-spedizione', 'view-dashboard-operatore',
            'edit-note-consegne', 'edit-fase',
        ]);

        // Fiery contatori
        Role::findByName('fiery_contatori', $guard)->syncPermissions([
            'view-dashboard-operatore',
        ]);

        // Viewer: solo lettura base
        Role::findByName('viewer', $guard)->syncPermissions([
            'view-dashboard-operatore', 'view-report',
        ]);

        // ═══════════════════════════════════════
        // MIGRA OPERATORI ESISTENTI
        // ═══════════════════════════════════════
        $operatori = Operatore::all();
        $migrati = 0;

        foreach ($operatori as $op) {
            // Se ha già un ruolo spatie, salta
            if ($op->roles->count() > 0) continue;

            $ruolo = $op->ruolo ?? 'operatore';

            // Mappa il campo ruolo al ruolo spatie
            if (Role::where('name', $ruolo)->where('guard_name', $guard)->exists()) {
                $op->assignRole($ruolo);
                $migrati++;
            } else {
                // Ruolo sconosciuto → assegna operatore
                $op->assignRole('operatore');
                $migrati++;
            }
        }

        $this->command->info("Ruoli creati: " . count($roles));
        $this->command->info("Permessi creati: " . count($permissions));
        $this->command->info("Operatori migrati: {$migrati}");
    }
}
