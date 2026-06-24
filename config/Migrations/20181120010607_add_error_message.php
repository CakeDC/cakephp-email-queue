<?php
use Migrations\BaseMigration;

class AddErrorMessage extends BaseMigration
{
    public function change()
    {
        $table = $this->table('email_queue');
        $table->addColumn('error', 'text', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
