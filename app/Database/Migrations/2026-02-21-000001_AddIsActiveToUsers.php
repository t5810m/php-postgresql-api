<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'is_active' => [
                'type'       => 'BOOLEAN',
                'not_null'   => true,
                'default'    => true,
                'after'      => 'location_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'is_active');
    }
}
