<?php

namespace App\Http\Middleware;

use Closure;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */

    public function handle($request, Closure $next)
    {
        
        // allow options request for cron origin;
        if ($request->getMethod() === "OPTIONS") {
            return response('');
        }
        // always make request "AJAX" when in the apiresponse middle ware
        // this will stop Laravel validators from redirecting to the "previous" page
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        // run this middleware api response after request
        // do this by firing the private api method after the pipeline is handled
        $response  =  $next($request);

        return  $this->api($request, $response);
    }

    //is called from handle and passed the closure and request and checks if it is not a 500
    private function api($request, $response)
    {
        // Checks if there is an exception and handles it appropriately
        if (isset($response->exception) && $response->exception) {
            if (get_parent_class($response->exception) == 'App\Exceptions\ApiException') {
                return $this->error($response->exception->getMessage(), $response->exception->getStatusCode(), $response->exception->getErrors());
            }

            if (get_class($response->exception) == 'Illuminate\Validation\ValidationException') {
                if(current($response->exception->errors()) && current(current($response->exception->errors()))){
                    return $this->validationError(current(current($response->exception->errors())), 422, $response->exception->errors());
                }else{
                    return $this->validationError("Validation Errors", 422, $response->exception->errors());
                }
            }
            return $this->response($response->content(),$response->getOriginalContent()['message'], $response->getStatusCode());
        } elseif (get_class($response) == "Illuminate\\Http\\JsonResponse") {
            $code = $response->getStatusCode();
            $responseData = $response->getData();
        } elseif (is_object($response->getOriginalContent()) && get_class($response->getOriginalContent()) == "Illuminate\\View\\View") {
            $code = $response->getStatusCode();
            $responseData = $response->getContent();
        } else {
            // tests to see what http method was used and respond with the appropriate code
            $method = strtolower($request->method());
            $code = $response->getOriginalContent() ? \Config::get("httpresponse.$method.success") : \Config::get("httpresponse.$method.failure");
            $responseData = $response->getOriginalContent();
        }
        $data = !empty($responseData->data) ? $responseData->data : null;
        $message = !empty($responseData->message)?$responseData->message : __('message.records_retrieved_successfully');

        return $this->response($data, $message, $code);
    }

    private function response($value, $message, $code=200)
    {
        //is called in all HTTP request methods to handle the structure of data returned.
        return response(
            [
                "message" => $message,
                "status" => $code,
                "data" => $value
            ], $code
        );
    }

    private function error($value, $code, $detail = null)
    {
        $errors = [];
        $errors['message'] = $value;
        if ($detail) {
            $errors['detail'] = $detail;
        }

        return  response(
            [
                "message" => $value,
                "status" => $code,
                "errors" => $errors
            ], $code
        );
    }

    private function validationError($value, $code, $errors = null)
    {
        $message = $value;

        if ($errors) {
            $errors = $errors;
        }

        return response([
            'message' => $message,
            'errors' => $errors,
            'status' => $code,
        ],$code);
    }
}
