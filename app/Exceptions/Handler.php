<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Traits\ApiResponser;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
class Handler extends ExceptionHandler
{
    use ApiResponser;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    

    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if($exception instanceof ValidationException)
        {
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        if($exception instanceof ModelNotFoundException){

            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("Does not exists any {$modelName} with the specified identificatior",404);
        }

        if($exception instanceof AuthenticationException)
        {
            return $this->unauthenticated($request,$exception);
        }

        if($exception instanceof AuthorizationException)
        {
            return $this->errorResponse($exception->getMessage(),403);
        }

        if($exception instanceof MethodNotAllowedHttpException)
        {
            return $this->errorResponse('The specified method for the request is invalid',405);
        }

        if($exception instanceof NotFoundHttpException)
        {
            return $this->errorResponse('The specified URL can not be found!',403);
        }

        if($exception instanceof HttpException)
        {
            return $this->errorResponse($exception->getMessage(),$exception->getStatusCode());
        }

        if($exception instanceof QueryException)
        {
            //dd($exception);
            $errorCode = $exception->errorInfo[1];

            if($errorCode == 1451){
                return $this->errorResponse("Cannot remove this resource permanently. It is related with any other resource",409);
            }
        }
        
        //return $this->errorResponse('Unexpected Exception. Try later', 500);

        return parent::render($request, $exception);
    }


    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        //dd("eroor");
        $errors = $e->validator->errors()->getMessages();

        return $this->errorResponse($errors,422);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        /*return $request->expectsJson()
                    ? response()->json(['message' => $exception->getMessage()], 401)
                    : redirect()->guest(route('login'));*/
        return $this->errorResponse('Unauthenticated',401);
    }
}
