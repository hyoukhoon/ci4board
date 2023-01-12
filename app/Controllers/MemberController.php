<?php 
namespace App\Controllers;  
use CodeIgniter\Controller;
use App\Models\UserModel;
  
class MemberController extends BaseController
{
    public function login()
    {
        echo render('login');
    } 

    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/board');
    } 
  
    public function loginok()
    {
        $db = db_connect();//디비연결
        $userid = $this->request->getVar('userid');//변수
        $passwd = $this->request->getVar('passwd');//변수
        $passwd = hash('sha512',$passwd);//암호화
        $query = "select * from members where userid='".$userid."' and passwd='".$passwd."'";
        //error_log ('['.__FILE__.']['.__FUNCTION__.']['.__LINE__.']['.date("YmdHis").']'.print_r($query,true)."\n", 3, './php_log_'.date("Ymd").'.log');//로그를 남긴다.
        $rs = $db->query($query);
        if($rs){//사용자가 맞으면
                $ses_data = [
                    'userid' => $rs->getRow()->userid,
                    'username' => $rs->getRow()->username,
                    'email' => $rs->getRow()->email
                ];
                $this->session->set($ses_data);//해당 사용자의 데이타를 배열에 담아서 세션에 저장한다.
                return redirect()->to('/board');//이동한다.
        }else{
            return redirect()->to('/login');
        }
    }
}