<?php
    
    namespace App\Http\Controllers;
    
    use Illuminate\Http\Request;
    use App\PhoneValidator;
    
    class PhoneValidatorController extends Controller
    {
        public function index(Request $request){
            $phoneValidator = new PhoneValidator();
            $phoneNumber = $request->phone;
            
            $result = $phoneValidator->carrier($phoneNumber);
            return $result;
        }
    }