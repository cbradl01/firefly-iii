<?php

namespace FireflyIII\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PfinanceProxyController extends Controller
{
    private $microserviceUrl = 'http://host.docker.internal:5001';

    /**
     * Proxy all requests to the PFinance microservice
     */
    public function proxy(Request $request, $path = '')
    {
        try {
            // Debug logging
            Log::info('PFinance proxy request', [
                'path' => $path,
                'method' => $request->method(),
                'content' => $request->getContent(),
                'headers' => $request->headers->all()
            ]);
            
            // Build the target URL
            $targetUrl = $this->microserviceUrl . '/' . $path;
            
            // Get the request method
            $method = $request->method();
            
            // Get request headers (excluding problematic ones)
            $headers = $request->headers->all();
            unset($headers['host']);
            unset($headers['content-length']);
            
            // Prepare the request
            $httpRequest = Http::withHeaders($headers);
            
            // Add request body for POST/PUT/PATCH requests
            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $httpRequest = $httpRequest->withBody($request->getContent(), 'application/json');
            }
            
            // Make the request to the microservice
            $response = $httpRequest->$method($targetUrl, $request->query());
            
            // Debug logging
            Log::info('PFinance proxy response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            // Return the response with the same status code and headers
            $responseObj = response($response->body(), $response->status())
                ->withHeaders($response->headers());
            
            return $responseObj;
                
        } catch (\Exception $e) {
            Log::error('PFinance proxy error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Proxy error',
                'message' => 'Failed to connect to PFinance microservice',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
