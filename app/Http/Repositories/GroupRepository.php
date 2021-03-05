<?php


namespace App\Http\Repositories;


use App\Http\Interfaces\GroupInterface;
use App\Http\Resources\GroupResource;
use App\Http\Traits\ApiResponse;
use App\Http\Traits\Upload;
use App\Models\Group;
use Validator;

class GroupRepository implements GroupInterface
{
    use ApiResponse, Upload;

    /**
     * @var User
     */
    private $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }

    public function getAllGroups()
    {
        $groups = $this->group::get();

        return $this->apiResponse(200,'All groups',null, GroupResource::collection($groups));
    }

    public function addGroup($request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|min:5',
            'body' => 'required|min:5',
            'image' => 'required',
            'teacher_id' => 'required|exists:users,id',
        ]);

        if($validator->fails()){
            return $this->apiResponse(422,'Error',$validator->errors());
        }

        $this->group->create([
            'name' => $request->name,
            'body' => $request->body,
            'image' => $this->upload('groups', $request->image),
            'teacher_id' => $request->teacher_id,
            'created_by' => auth('api')->id(),
        ]);
        return $this->apiResponse(200,'Added Successfully');
    }

    public function updateGroup($request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|min:5',
            'body' => 'required|min:5',
            'teacher_id' => 'required|exists:users,id',
            'group_id' => 'required|exists:groups,id'
        ]);

        if($validator->fails()){
            return $this->apiResponse(422,'Error',$validator->errors());
        }
        $group = $this->group::find($request->group_id);

        $image = $group->image;

        if($request->hasFile('image')){

            $this->deleteFile('images/groups/' . $image);
            $image = $this->upload('groups', $request->image);
        }

        $group->update([
            'name' => $request->name,
            'body' => $request->body,
            'image' => $image,
            'teacher_id' => $request->teacher_id,
            'created_by' => auth('api')->id(),
        ]);
        return $this->apiResponse(200,'Updated Successfully');
    }

    public function getGroup($request)
    {
        $validator = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
        ]);

        if($validator->fails()){
            return $this->apiResponse(422,'Error',$validator->errors());
        }
        $group = $this->group::with('teacher')->find($request->group_id);
        return $this->apiResponse(200,'group data', null, new GroupResource($group));
    }

    public function deleteGroup($request)
    {
        $validator = Validator::make($request->all(),[
            'group_id' => 'required|exists:groups,id',
        ]);

        if($validator->fails()){
            return $this->apiResponse(422,'Error',$validator->errors());
        }
        $group = $this->group::find($request->group_id);
        $this->deleteFile('images/groups/' . $group->image);

        $group->delete();

        return $this->apiResponse(200,'Deleted Successfully');
    }
}
