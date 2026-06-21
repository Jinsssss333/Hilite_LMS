<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\LeadIntakeService;
use App\Services\PhoneNormalizationService;
use App\Services\AuditLogService;

$company = Company::first();
if (!$company) {
    echo "No company found.\n";
    exit;
}

$salesperson = User::where('company_id', $company->id)->where('role', 'salesperson')->first();
$manager = User::where('company_id', $company->id)->where('role', 'manager')->first();

if (!$salesperson) {
    echo "No salesperson found.\n";
    exit;
}

$service = new LeadIntakeService(new PhoneNormalizationService(), new AuditLogService());

echo "Testing Manual Addition by Salesperson...\n";
DB::beginTransaction();
try {
    $data = [
        'name' => 'Self Generated Lead',
        'phone' => '555-000-1111',
        'email' => 'self@example.com',
        'source' => 'manual',
        'assigned_user_id' => $salesperson->id // The controller will inject this now!
    ];
    $result = $service->intake($data, $company->id, $salesperson->id);
    
    echo "Assigned User ID: " . $result['engagement']->assigned_user_id . "\n";
    echo "Salesperson ID: " . $salesperson->id . "\n";
    
    if ($result['engagement']->assigned_user_id == $salesperson->id) {
        echo "SUCCESS: Salesperson got their own lead.\n";
    } else {
        echo "FAIL: Lead went to someone else (User ID: " . $result['engagement']->assigned_user_id . ").\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
DB::rollBack();
