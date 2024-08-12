<?php namespace AliKhaleghi\BaseSys\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_user_meta_table extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 255,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true ],
            'meta_key' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'meta_value' => [
                'type' => 'TEXT',
                'constraint' => '255',
            ],
            
        ]);
		$this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('user_meta');
    }

    public function down()
    {
        $this->forge->dropTable('user_meta');
    }
}