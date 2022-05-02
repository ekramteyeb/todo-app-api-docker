<?php
   
namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\Todo as TodoResource;
use App\Models\Todo;
use Validator;
use Illuminate\Support\Facades\Gate;


class TodoController extends BaseController
{

    public function index(Request $request)
    {
        //$todos = Todo::all();
       
        $todos = Todo::where('user_id', Auth::id())->when(isset($request->status), function($query) use ($request) {
            $query->where('status','=', $request->status);
        })->get();
        return $this->handleResponse(TodoResource::collection($todos), 'Todos have been retrieved!');
    }
    

    
    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'status' => 'nullable',/* , Rule::in(['NotStarted','OnGoing','Completed']) */
            'user_id' => 'nullable',
           
        ]);
        if($validator->fails()){
            //return $this->handleError($validator->errors());       
            return $this->handleError($validator->errors());       
        }
        //$input->user_id = Auth::id();
        $input['user_id'] = Auth::id();
        $input['status'] = $request['status'] ? $input['status'] : 'NotStarted'; 
        $todo = Todo::create($input);
        return $this->handleResponse(new TodoResource($todo), 'Todo created!');
    }

    public function show($id)
    {
    
        $todo = Todo::find($id);
        if (is_null($todo) || ($todo['user_id'] !== Auth::id()) ) {
            return $this->handleError('Todo not found!');
        }
        return $this->handleResponse(new TodoResource($todo), 'Todo retrieved.');
    }
    

    public function update(Request $request, Todo $todo)
    {
        //autorization done by Gate which is configured in Providers/AuthServiceProvider.php
        if(!Gate::allows('update-todo', $todo)){
            abort(403);
        }

        //Similarly authorization can be done manually like this ,check if the user is the owner of the todo
        /* if( $todo['user_id'] != Auth::id()){
            return $this->handleError('Unauthorized operation'); 
        } */
        $input = $request->all();

        $validator = Validator::make($input, [
            'status' => 'nullable',
            'description' => 'nullable' ,
            'name' => 'nullable'
        ]);

        if($validator->fails()){
            return $this->handleError($validator->errors());       
        }
         
        //check if a given column value is provided and insert , else leave intact 
        $todo->name = $request->name ? $input['name'] : $todo->name;
        $todo->description = $request->description ? $input['description'] : $todo->description ;
        $todo->status = $request->status ? $input['status'] : $todo->status;
        $todo->save();
        
        return $this->handleResponse(new TodoResource($todo), 'Todo successfully updated!');
    }
   
    public function destroy(Todo $todo)
    {
        //check if the user is the owner of the todo
        if(!Gate::allows('delete-todo', $todo)){
            abort(403);
        }
        /* if( $todo['user_id'] != Auth::id()){
            return $this->handleError('Unauthorized operation'); 
        } */
        $todo->delete();
        return $this->handleResponse([], 'Todo deleted!');
    }
}