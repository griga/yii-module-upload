<?php

class m140416_003333_upload extends DbMigration
{
    public function up()
    {
        $this->createTable('{{upload}}', [
            'id'=>'pk',
            'entity'=>'VARCHAR(25) NOT NULL',
            'entity_id'=>'INT NOT NULL',
            'user_id'=>'INT',
            'filename'=>'VARCHAR(255) NOT NULL',
            'meta'=>'TEXT',
            'sort'=>'INT NOT NULL DEFAULT 0',
            'create_time'=>'TIMESTAMP',
            'update_time'=>'TIMESTAMP',
        ]);

        $this->createIndex('upload_entity_index','{{upload}}','entity, entity_id');
    }

    public function down()
    {
        $this->dropIndex('upload_entity_index','{{upload}}');
        $this->dropTable('{{upload}}');

    }


}