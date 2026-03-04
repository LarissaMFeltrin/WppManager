<?php

use yii\db\Migration;

class m260304_000001_add_cliente_aguardando_desde extends Migration
{
    public function safeUp()
    {
        $this->addColumn('conversas', 'cliente_aguardando_desde', $this->dateTime()->null()->defaultValue(null)->after('ultima_msg_em'));
    }

    public function safeDown()
    {
        $this->dropColumn('conversas', 'cliente_aguardando_desde');
    }
}
