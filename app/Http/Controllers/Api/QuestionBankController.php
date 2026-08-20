<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Repositories\QuestionBankRepositoryInterface;
use App\Http\Controllers\Api\Concerns\AuthorizesStaff;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionBankIndexRequest;
use App\Http\Requests\StoreQuestionBankQuestionRequest;
use App\Http\Requests\UpdateQuestionBankQuestionRequest;
use App\Http\Resources\QuestionBankQuestionResource;
use App\Models\QuestionBankQuestion;
use Illuminate\Http\Request;

class QuestionBankController extends Controller
{
    use AuthorizesStaff;

    public function __construct(private readonly QuestionBankRepositoryInterface $questions)
    {
    }

    public function index(QuestionBankIndexRequest $request)
    {
        return QuestionBankQuestionResource::collection($this->questions->paginate($request->validated()));
    }

    public function store(StoreQuestionBankQuestionRequest $request)
    {
        $question = $this->questions->create($request->validated(), $request->user());

        return (new QuestionBankQuestionResource($question))->response()->setStatusCode(201);
    }

    public function show(Request $request, QuestionBankQuestion $questionBankQuestion)
    {
        $this->authorizeStaff($request);

        return new QuestionBankQuestionResource($this->questions->show($questionBankQuestion));
    }

    public function update(UpdateQuestionBankQuestionRequest $request, QuestionBankQuestion $questionBankQuestion)
    {
        return new QuestionBankQuestionResource($this->questions->update($questionBankQuestion, $request->validated()));
    }

    public function destroy(Request $request, QuestionBankQuestion $questionBankQuestion)
    {
        $this->authorizeStaff($request);
        $this->questions->delete($questionBankQuestion);

        return response()->noContent();
    }
}
