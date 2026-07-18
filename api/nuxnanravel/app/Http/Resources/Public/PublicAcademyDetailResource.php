<?php
namespace App\Http\Resources\Public; use Illuminate\Http\Request;
class PublicAcademyDetailResource extends PublicAcademyResource{public function toArray(Request $r):array{return array_merge(parent::toArray($r),['description'=>$this->description,'support_summary'=>$this->support_summary??null,'courses'=>$this->courses()->select(['id','slug','name','title','cover'])->get()]);}}
