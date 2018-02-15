<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiProxyController extends Controller
{
    /**
     * @Route("/apicfn", name="apicfn")
     */
    public function proxyAction(Request $request)
    {
        
         // Forbid every request but jquery's XHR
//         if (!$request->isXmlHttpRequest()) {// isn't it an Ajax request?
//             return new Response('', 404, 
//                             array('Content-Type' => 'application/json'));
//         }

//        $remoteUrl = $request->query->get('remoteUrl');
//        
//        $restUrl = "http://probando/asdfadf/dsfeffd";
//        $url = $request->getRequestUri();
//        print_r($remoteUrl);
//        print_r('-------');
//        print_r($url);
//        print_r('-------');
//        $url = str_replace("/apicfn", $restUrl, $url);
//        print_r($url);
//        exit;
         
//         $request->isXmlHttpRequest(); // is it an Ajax request?
//    $request->getPreferredLanguage(array('en', 'fr'));
//    // retrieves GET and POST variables respectively
//    $request->query->get('page');
//    $request->request->get('page');
//    // retrieves SERVER variables
//    $request->server->get('HTTP_HOST');
//    // retrieves an instance of UploadedFile identified by foo
//    $request->files->get('foo');
//    // retrieves a COOKIE value
//    $request->cookies->get('PHPSESSID');
//    // retrieves an HTTP request header, with normalized, lowercase keys
//    $request->headers->get('host');
//    $request->headers->get('content_type');
         
//         getRequestUri()
//         getMethod()
//         getRealMethod()
//         getContentType()
//         getContent(bool $asResource = false)
         
         
         
         
         
         
         //$remoteUrl = $request->request->get('remoteUrl');
         $remoteUrl = $request->query->get('remoteUrl');
         //$method = $request->request->get('method');
         $method = $request->getMethod();
         $params = $request->request->get('params');
         $contentType = $request->request->get('contentType');

//         print_r($remoteUrl);
//         print_r('-------');
//         print_r($method);
//         print_r('-------');
//         print_r($params);
//         print_r('-------');
//         print_r($contentType);
//         print_r('-------');
//         exit;
         
         if ($contentType == null) {
             $contentType = 'application/json';
         }

         if ($remoteUrl == null || $method == null || 
                         !in_array($method, array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'))) {
             return new Response('', 404, array('Content-Type' => $contentType));
         }

         $this->enable_cors();
         session_write_close();
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $remoteUrl);
         curl_setopt($ch, CURLOPT_HEADER, true);
         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
         if ($params != null) {
             curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
         }
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

         $requestCookies = $request->cookies->all();

         $cookieArray = array();
         foreach ($requestCookies as $cookieName => $cookieValue) {
             $cookieArray[] = "{$cookieName}={$cookieValue}";
         }
         $cookie_string = implode('; ', $cookieArray);
         curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);

         $curl_response = curl_exec($ch);
         curl_close($ch);
//print_r($response); exit;
         list($headers, $curl_response) = explode("\r\n\r\n",$curl_response,2);
         preg_match_all('/Set-Cookie: (.*)\b/', $headers, $cookies);
         $cookies = $cookies[1];

         if ($curl_response === false) {
             return new Response('', 404, array('Content-Type' => $contentType));
         } else {
             $response = new Response($curl_response, 200, 
                                              array('Content-Type' => $contentType));
             foreach($cookies as $rawCookie) {
                 $cookie = \Symfony\Component\BrowserKit\Cookie::fromString($rawCookie);
                 $value = $cookie->getValue();
                 if (!empty($value)) {
                     $value = str_replace(' ', '+', $value);
                 }
                 $customCookie = new \Symfony\Component\HttpFoundation\Cookie($cookie->getName(), $value, $cookie->getExpiresTime()==null?0:$cookie->getExpiresTime(), $cookie->getPath());
                 $response->headers->setCookie($customCookie);
             }
             return $response;
         }

    }
    
    /**
	 *  An example CORS-compliant method.  It will allow any GET, POST, or OPTIONS requests from any
	 *  origin.
	 *
	 *  In a production environment, you probably want to be more restrictive, but this gives you
	 *  the general idea of what is involved.  For the nitty-gritty low-down, read:
	 *
	 *  - https://developer.mozilla.org/en/HTTP_access_control
	 *  - http://www.w3.org/TR/cors/
	 *
	 */
	protected function enable_cors() {
		// Allow from any origin
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');;
			header('Access-Control-Max-Age: 86400');	// cache for 1 day
		} else {
			header("Access-Control-Allow-Origin: *");
			header('Access-Control-Allow-Credentials: true');;
			header('Access-Control-Max-Age: 86400');	// cache for 1 day
		}
		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");		 
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
			exit(0);
		}
	}
}
