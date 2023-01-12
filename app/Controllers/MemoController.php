<?php

namespace App\Controllers;
use App\Models\BoardModel;//사용할 모델을 반드시 써줘야한다.

class MemoController extends BaseController
{
    public function memo_write()
    {
        if(!isset($_SESSION['userid'])){
            return "login";
            exit;
        }
        
        $db = db_connect();
        $memo=$this->request->getVar('memo');
        $bid=$this->request->getVar('bid');
        $file_table_id=$this->request->getVar('file_table_id');

        $sql="INSERT INTO memo
            (bid, userid, memo, status)
            VALUES(".$bid.", '".$_SESSION['userid']."', '".$memo."', 1)";
        $rs = $db->query($sql);
        $insertid=$db->insertID();
        //error_log ('['.__FILE__.']['.__FUNCTION__.']['.__LINE__.']['.date("YmdHis").']'.print_r($file_table_id,true)."\n", 3, './php_error_'.date("Ymd").'.log');
        if(!empty($file_table_id)){//첨부한 파일이 있는 경우에만
            $uq="update file_table set bid=".$bid.", memoid=".$insertid." where fid=".$file_table_id;
            $uqs = $db->query($uq);
            $fquery="select * from file_table where status=1 and fid=".$file_table_id;
            $rs2 = $db->query($fquery);
            $imgarea = "<img src='/uploads/".$rs2->getRow()->filename."' style='max-width:90%'>";
        }else{
            $imgarea="";
        }

        $return_data = "<div class=\"card mb-4\" id=\"memo_".$insertid."\" style=\"max-width: 100%;margin-top:20px;\">
                <div class=\"row g-0\">
                    <div class=\"col-md-12\">
                    <div class=\"card-body\">
                    <p class=\"card-text\">".$imgarea."<br>".$memo."</p>
                    <p class=\"card-text\"><small class=\"text-muted\">".$_SESSION['userid']." / now</small></p>
                    <p class=\"card-text\" style=\"text-align:right\"><a href=\"javascript:;\" onclick=\"memo_modify(".$insertid.")\"><button type=\"button\" class=\"btn btn-secondary btn-sm\">수정</button></a>&nbsp;<a href=\"javascript:;\" onclick=\"memo_del(".$insertid.")\"><button type=\"button\" class=\"btn btn-secondary btn-sm\">삭제</button></a></p>
                    </div>
                </div>
                </div>
            </div>";
        return $return_data;
    }

    public function save_image_memo()
    {
        $db = db_connect();

        if(!isset($_SESSION['userid'])){
            $retun_data = array("result"=>"fail", "data"=>"login");
            return json_encode($retun_data);
            exit;
        }
        
        $file = $this->request->getFile('savefile');
            if($file->getName()){
                $filename = $file->getName();
                //$filepath = WRITEPATH. 'uploads/' . $file->store();
                $newName = $file->getRandomName();
                $filepath = $file->store('memo/', $newName);
            }

            if(isset($filepath)){
                $sql2="INSERT INTO file_table
                        (bid, userid, filename, type)
                        VALUES('', '".$_SESSION['userid']."', '".$filepath."', 'memo')";
                $rs2 = $db->query($sql2);
                $insertid=$db->insertID();                
            }

        $retun_data = array("result"=>"success", "fid"=>$insertid, "savename"=>$filepath);
        return json_encode($retun_data);
    }

    public function memo_file_delete()
    {
        $db = db_connect();
        $fid=$this->request->getVar('fid');
        $query = "select * from file_table where type='memo' and fid=".$fid;
        $rs = $db->query($query);
        if(unlink('uploads/'.$rs->getRow()->filename)){
            $query2= "delete from file_table where type='memo' and fid=".$fid;
            $rs2 = $db->query($query2);
            $retun_data = array("result"=>"ok");
            return json_encode($retun_data);
        }else{
            $retun_data = array("result"=>"no");
            return json_encode($retun_data);
        }
    }

    public function memo_delete()
    {
        if(!isset($_SESSION['userid'])){//로그인여부
            $retun_data = array("result"=>"login");
            return json_encode($retun_data);
            exit;
        }
        $db = db_connect();
        $memoid=$this->request->getVar('memoid');
        $query = "select * from memo where memoid=".$memoid;
        $rs = $db->query($query);
        if($memoid and $rs->getRow()->memoid){//memoid가 있는지 또는 메모가 테이블에 있는지
            if($rs->getRow()->userid==$_SESSION['userid']){//본인이 작성한 메모인지
                $query2= "delete from memo where memoid=".$memoid;
                if($rs2 = $db->query($query2)){//삭제했는지
                    $query3 = "select * from file_table where type='memo' and bid=".$rs->getRow()->bid." and memoid=".$memoid;
                    $rs3 = $db->query($query3);
                    if(isset($rs3->getRow()->filename) and unlink('uploads/'.$rs3->getRow()->filename)){
                        $query4= "delete from file_table where fid=".$rs3->getRow()->fid;
                        $rs4 = $db->query($query4);
                    }
                    $retun_data = array("result"=>"ok");
                    return json_encode($retun_data);
                    exit;
                }else{
                    $retun_data = array("result"=>"fail");
                    return json_encode($retun_data);
                    exit;
                }
            }else{
                $retun_data = array("result"=>"my");
                return json_encode($retun_data);
                exit;
            }
        }else{
            $retun_data = array("result"=>"nodata");
            return json_encode($retun_data);
            exit;
        }
        
        
    }


    public function memo_modify()//댓글 수정 버튼을 눌렀을때 작동
    {
        $db = db_connect();
        $memoid=$this->request->getVar('memoid');
        $query = "select * from memo where memoid=".$memoid;
        $rs = $db->query($query);
            if($rs->getRow()->userid==$_SESSION['userid']){//본인이 작성한 메모인지
                $query3 = "select * from file_table where type='memo' and memoid=".$memoid;
                $rs3 = $db->query($query3);
                $html = "<form class=\"row g-3\">
                    <input type=\"hidden\" id=\"modify_memoid\" value=\"".$memoid."\">
                    <input type=\"hidden\" id=\"modify_file_table_id\" value=\"\">

                    <div class=\"col-md-8\" style=\"padding:10px;\">
                        <textarea class=\"form-control\" id=\"memo_text_".$rs->getRow()->memoid."\" style=\"height: 60px\">".$rs->getRow()->memo."</textarea>
                    </div>
                    <div class=\"col-md-2\" style=\"padding:10px;\">
                        <button type=\"button\" class=\"btn btn-secondary\" onclick=\"memo_modify_update(".$rs->getRow()->memoid.")\" >댓글수정</button>
                    </div>";
                if(isset($rs3->getRow()->fid)){
                    $html .= "<div class=\"col-md-2\" style=\"padding:10px;\" id=\"memo_image_".$memoid."\">
                                <div style=\"display:none;\" class=\"btn btn-warning\" id=\"filebutton_".$memoid."\" onclick=\"$('#upfile').click();\">사진첨부</div>
                                <input type=\"file\" name=\"upfile\" class=\"upfile\" id=\"upfile_".$memoid."\" style=\"display:none;\" />
                                <div class=\"col\" id=\"f_".$rs3->getRow()->fid."\"><div class=\"card h-100\"><img src=\"/uploads/".$rs3->getRow()->filename."\" class=\"card-img-top\"><div class=\"card-body\"><button type=\"button\" class=\"btn btn-warning\" onclick=\"memo_file_del(".$rs3->getRow()->fid.")\">삭제</button></div></div></div>
                            </div>";
                }else{
                    $html .= "<div class=\"col-md-2\" style=\"padding:10px;\" id=\"memo_image_".$memoid."\">
                                <div class=\"btn btn-warning\" id=\"filebutton_".$memoid."\" onclick=\"$('#upfile').click();\">사진첨부</div>
                                <input type=\"file\" name=\"upfile\" id=\"upfile\" style=\"display:none;\" />
                            </div>";
                }
                    $html .= "</form>";
                echo $html;
            }else{
                echo "my";
                exit;
            }
    }

    public function memo_modify_update()//댓글을 수정 후 저장할때 작동
    {
        $db = db_connect();
        $memoid=$this->request->getVar('memoid');
        $memo_text=$this->request->getVar('memo_text');
        $modify_file_table_id=$this->request->getVar('modify_file_table_id');
        $query = "select * from memo where memoid=".$memoid;
        $rs = $db->query($query);
            if($rs->getRow()->userid==$_SESSION['userid']){//본인이 작성한 메모인지
                $uq="update memo set memo='".$memo_text."' where memoid=".$memoid;
                $uqs = $db->query($uq);

                if(!empty($modify_file_table_id)){//첨부한 파일이 있는 경우에만
                    $uq="update file_table set bid=".$rs->getRow()->bid.", memoid=".$memoid." where fid=".$modify_file_table_id;
                    $uqs = $db->query($uq);
                }

                $query3 = "select * from file_table where type='memo' and memoid=".$memoid;
                $rs3 = $db->query($query3);

                if(!empty($rs3->getRow()->filename)){//첨부한 파일이 있는 경우에만
                    $imgarea = "<img src='/uploads/".$rs3->getRow()->filename."' style='max-width:90%'>";
                }else{
                    $imgarea="";
                }

                $return_data = "<div class=\"row g-0\">
                            <div class=\"col-md-12\">
                            <div class=\"card-body\">
                            <p class=\"card-text\">".$imgarea."<br>".$memo_text."</p>
                            <p class=\"card-text\"><small class=\"text-muted\">".$_SESSION['userid']." / now</small></p>
                            <p class=\"card-text\" style=\"text-align:right\"><a href=\"javascript:;\" onclick=\"memo_modify(".$memoid.")\"><button type=\"button\" class=\"btn btn-secondary btn-sm\">수정</button></a>&nbsp;<a href=\"javascript:;\" onclick=\"memo_del(".$memoid.")\"><button type=\"button\" class=\"btn btn-secondary btn-sm\">삭제</button></a></p>
                            </div>
                        </div>
                        </div>";
                return $return_data;
            }else{
                echo "my";
                exit;
            }
    }

    
}
