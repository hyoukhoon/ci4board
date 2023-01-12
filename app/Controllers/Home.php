<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $query = "select * from board where bid=329";
        $rs = $db->query($query);
        $data['view'] = $rs->getRow();
        return view('welcome_message', $data);
    }
}
