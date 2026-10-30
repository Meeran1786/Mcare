<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    public function clearData(Request $request)
    {
        // 🔐 Secret key check
        if ($request->key !== '123456') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid key'
            ], 403);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::select('SHOW TABLES');

        // ✅ Tables to KEEP (don't delete data)
        $skipTables = ['migrations', 'admins', 'banners' ,'pages', 'faqs', 'services', 'social_links', 'app_settings', 'languages','translations'];
         
  

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];

            // ❌ Skip selected tables
            if (in_array($tableName, $skipTables)) {
                continue;
            }

            DB::table($tableName)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json([
            'success' => true,
            'message' => 'All data cleared except admins'
        ]);
    }
}