<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PlayerId extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'LONGTEXT'
            ],
            'player_id' => [
                'type' => 'LONGTEXT'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('notif');
    }

    public function down()
    {
        $this->forge->dropTable('notif');
    }
}
