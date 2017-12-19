<?php

namespace App\Http\Controllers;

use App\Mission;
use App\MissionRequest;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Author;

class MissionController extends Controller
{
    //
    public function index(Request $request)
    {
        $missionList = Mission::where('serverNumber', $request->query('server'))->orderBy('gameType')->orderBy('max','desc')->get();
        if(auth()->check()){
           if(auth()->user()->isRoleOrAbove('Game Admin')){
               $disabled = false;
           }
           else
               $disabled = true;
        }
        else{
            $disabled = true;
        }

        $authorList = $this->GetAuthorList();



        return view('missions.index', compact(['missionList','disabled','authorList']));
    }

    public function userMissions($id)
    {
        $missionList = Mission::where('user_id', $id)->get();
        $disabled = false;
        $authorList = $this->GetAuthorList();
        return view('missions.index', compact(['missionList','disabled','authorList']));
    }


    //Accessed via update form and ajax requests from mission page, redirects to mission list of where the updated mission is from
    public function Update(Request $request,$id){
        Mission::find($id)->update($request->all());
            $mis = Mission::find($id);
            if ($mis->status == "Pending Details" && strpos($request->header('referer'),'mission/') != false) { //Only set to new if the page we came from was the mission update page. Hacky fix but works
                $mis->status = "New";
                $mis->save();
            }
        \Log::info(auth()->user()->name . " Updated " . $mis->fileName);
        return redirect('/missions?server=' . Mission::find($id)->serverNumber);
    }


    public function UpdatePage($id){
        $mission = Mission::find($id);
        if(auth()->user()->isRoleOrAbove('Game Admin') || $mission->hasAuthorID(auth()->id())) {
            return view("missions.update", compact('mission'));
        }
        else dd('NO ACCESS');
    }

    public function AddMission(){
        if(!auth()->user()->isRoleOrAbove('Mission Dev')){
            //Shouldn't be here
            return redirect('/');
        }
        $mRequest = new MissionRequest();
        $mRequest->fileName = \request()->fileName;
        $mRequest->user_id = auth()->user()->id;


        try{
            $mRequest->save();
            return back()->with('status-success','Mission Request Added');
        }
        catch(\Exception $e){
            return back()->with('status-danger',$e->getCode());
        }

    }

    public function Download(){

    }
    public function Delete(Mission $mission){
        try {
            $mission->delete();
            \Log::info(auth()->user()->name . " Deleted " . $mission->fileName);
            return ("Deleted Successfully");
        }
        catch (\Exception $e){
            return $e->getMessage();
        }

    }


    private function GetAuthorList()
    {
        return  User::where(function ($query){
            return $query->where('role','Mission Dev')
                ->orWhere('role','Game Admin')
                ->orWhere('role','Senior Admin')->
                orWhere('role','Super Admin');
        })->get();
    }



}
