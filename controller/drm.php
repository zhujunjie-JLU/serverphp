<?php
require_once "tool.php";
//删除机房
class drm{
    public function run($request)
    {
        $errorReturn = array("status" => "fail", "errno" => "");
        $successReturn = array("status" => "success");
        if (!isset($request->get)
            || !isset($request->get["rmid"])) {
            $errorReturn["errno"] = "参数错误";
            return json_encode($errorReturn);
        }
        $rmid = $request->get["rmid"];
        $db = new \mtool\mysql();

        $res = $db->update("rmTable", ["available" => "0"], ["rmid" => $rmid]);
        if ($res["affected_rows"] === 0) {
            $errorReturn["errno"] = "错误的房间";
            return json_encode($errorReturn);
        }
        return json_encode($successReturn);
    }
}