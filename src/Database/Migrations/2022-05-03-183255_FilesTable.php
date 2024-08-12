<?php namespace AliKhaleghi\BaseSys\Database\Migrations;

use CodeIgniter\Database\Migration;

class FilesTable extends Migration
{
    public function up()
    {
        /*
         * Files
         */
        $this->forge->addField([
            'id'                => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'uploaded_by'       => ['type' => 'int', 'constraint' => 255],
            'name'              => ['type' => 'varchar', 'constraint' => 255],
            'caption'           => ['type' => 'varchar', 'constraint' => 255],
            'path'              => ['type' => 'text', 'constraint' => 255],
            'type'              => ['type' => 'varchar', 'constraint' => 255],
            'section'           => ['type' => 'varchar', 'constraint' => 255],
            'section_id'        => ['type' => 'varchar', 'constraint' => 255],
            'details'           => ['type' => 'text', 'constraint' => 255, 'null' => true],
            'created_at'        => ['type' => 'datetime', 'null' => true],
            'updated_at'        => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->createTable('files', true);
    }

    //--------------------------------------------------------------------

    public function down()
    {
		$this->forge->dropTable('files', true);
    }
}
