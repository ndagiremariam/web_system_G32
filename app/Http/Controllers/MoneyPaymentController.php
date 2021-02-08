<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MoneyModel;

class MoneyPaymentController extends Controller
{
    //
    public string $default_month;
    public  string $set_month;
    public $set =False;

    //get month

    protected function  getMonth(){

        if($this->set){
              $store_month = $this->set_month;
             //print_r("AM HERE");
            $donor_money = DB::select('select Month ,Amount from covid_19_total_funds where Month = ?', [$store_month]);
            //dd($donor_money);
            $this->default_month = $store_month;
            return $donor_money[0]->Amount;
            //return $donor_money[0]->Amount;
      
    
         }
         else{
          //part
            $donor_money = DB::table('covid_19_total_funds')
            ->select('Amount','Month')
            ->where('AmountId', '=', 1)->get();
            //dd($donor_money);

            if(count($donor_money)){
                //getting
                $this->default_month = $donor_money[0]->Month;
                return $donor_money[0]->Amount;
            }
            else{
                return;
            }

      
         }
    }
    protected function  format_currency($array_currency){
        return  array_map(function($currency){
              if($currency->MonthlySalary){
                 $currency->MonthlySalary = number_format($currency->MonthlySalary, 2, '.', ',');
                 return $currency;
              }
         }, $array_currency);
     }
  

   //gets the remaining amount
     private function  cal_payments($first_amount, $second_amount){
        $remaining_amount = (int)$first_amount-(int)$second_amount;
        return (int)$remaining_amount;
    }
    


    
   //format currencey
     
     protected function  format_currency_staff($array_currency){
        return  array_map(function($currency){
              if($currency->monthlysalary){
                 $currency->monthlysalary = number_format($currency->monthlysalary, 2, '.', ',');
                 return $currency;
              }
         }, $array_currency);
     }

     //pass to format
     protected function pass_to_format_staff($role){
        return DB::select('select * from users where role = ?', [$role]);
    }
     protected function count_officers($role){
         return DB::table('officers')
         ->where("OfficerRole","=", $role)
         ->count();
     }
     
     protected function update_payments($salary, $officerRole){
         return DB::update("update officers set MonthlySalary = $salary
         where OfficerRole = ?", [$officerRole]);
     }
     protected function update_payments_staff($salary, $officerRole){
        return DB::update("update users set MonthlySalary = $salary
        where role = ?", [$officerRole]);
    }


    
     protected function pass_to_format($hospitalCategory){
         return DB::select('select OfficerRole, MonthlySalary , OfficerName from officers where HospitalCategory = ?',
          [$hospitalCategory]);
     }
     
     
     protected function all_months(){
         return DB::select('select Month from covid_19_total_funds where Amount >?', [100000000]);
         
     }



    public function index(){

        $diff_money = $this->cal_payments((int)$this->getMonth(),(int)100000000 );
        if($diff_money >0){
            //diff amount
            $director_money_national_referal = 5000000;

            $remaining_amount = $this->cal_payments($diff_money, 5000000);

            
            $superintendent_money = $director_money_national_referal/2;
           //superintendent
            $remaining_after_superintendent = $this->cal_payments($remaining_amount, $superintendent_money);

            $administrator_money = $superintendent_money*(3/4);

            $remaining_after_admin = $this->cal_payments($remaining_after_superintendent, $administrator_money);

            //officers

            //calcutte total officers in general hospitals
            $total_officers_general = $this->count_officers('officer');
            //echo 'We are general'.$total_officers_general;
            
    
            //general officer salary
            $general_officer_salary = $administrator_money*(8/5);
             
            $total_officer_salary = $general_officer_salary;
            if($total_officers_general){
               $total_officer_salary = $total_officers_general*$total_officer_salary;
            }
            
            //$general_officer_salary*$total_officers_general
            $remaining_after_general_officer_salary = $this->cal_payments($remaining_after_admin, $total_officer_salary);
    
            //echo $remaining_after_general_officer_salary;
    
            //total senior officers
            $senior_officer_salary = $general_officer_salary + $general_officer_salary*(6/100);

            $total_senior_officers = $this->count_officers('senior officer');
            //echo $total_senior_officers;
            if($total_senior_officers){
                $total_senior_officer_salary = $total_senior_officers*$senior_officer_salary;
            }    

            $remaining_amount_after_senior_officers = $this->cal_payments($remaining_after_general_officer_salary, $total_senior_officers);
    
            //calcu head officer
            $head_officer_salary = $general_officer_salary + $general_officer_salary*(3.5/100);
    


    
            $remaining_after_head_officer_bonus = $this->
            cal_payments($remaining_amount_after_senior_officers, $head_officer_salary);
    

    
            $director_money_national_referal+=($remaining_after_head_officer_bonus*(5/100));
            $superintendent_total_salary = $director_money_national_referal/2;
            $admin_total_salary  = $superintendent_total_salary*(3/4);
            $officer_total_salary = $admin_total_salary*(8/5);
            $senior_total_salary = $officer_total_salary + $officer_total_salary*(6/100);
            $head_officer_total = $officer_total_salary + $officer_total_salary*(3.5/100);
    
            
          //updating different hospitals
            $this->update_payments($director_money_national_referal, 'director');
            $this->update_payments($superintendent_total_salary, 'superintendent');
            $this->update_payments($senior_total_salary, 'senior officer');
            $this->update_payments($officer_total_salary, 'officer');
            $this->update_payments($head_officer_salary, 'head');

            //update staff members
            $this->update_payments_staff($director_money_national_referal, 'Director');
            $this->update_payments_staff($admin_total_salary, 'Admin');
            


              $officers_at_general_hospitals = $this->format_currency($this->pass_to_format('general'));
               $officers_at_referal_hospitals = $this->format_currency($this->pass_to_format('regional'));
               $officers_at_national_hospitals = $this->format_currency($this->pass_to_format('national'));
               //staff members
               $Admin = $this->format_currency_staff($this->pass_to_format_staff('Admin'));

               return view('monthlypayment',
               [
               'officers_general'=>$officers_at_general_hospitals,
               'officers_regional'=>$officers_at_referal_hospitals,
               'officers_national'=>$officers_at_national_hospitals,
               'months'=>$this->all_months(),
               'default'=>$this->default_month,
               'staff'=>$Admin
               ]
            );
              
    
          }
          else {
              $staff_money = array();
              $officers_at_general_hospitals = array();
              $officers_at_referal_hospitals = array();
              $officers_at_national_hospitals = array();
              $months = array();
              $Admin = array();
            return view('monthlypayment',
            ['staff_payments'=>$staff_money,
            'officers_general'=>$officers_at_general_hospitals,
            'officers_regional'=>$officers_at_referal_hospitals,
            'officers_national'=>$officers_at_national_hospitals,
            'months'=>$this->all_months(),
            'staff'=>$Admin
            
            ]);
          }
    }
 //HERE
    public function store(Request $request){
        //dd($request);
        $this->validate($request,
        ['month'=>'required']);
        $this->set_month = $request->month;
        //dd($request->month);
        
        $this->set = True;
        
       //
       $diff_money = 
       $this->cal_payments((int)$this->getMonth(),(int)100000000);
    
           //dd($diff_money);
       
       if($diff_money >0){
          
        
       // dd("AM HERE");
        $remaining_amount = $this->cal_payments($diff_money, 5000000);
        $director_money_national_referal = 5000000;
        $superintendent_money = $director_money_national_referal/2;
        $remaining_after_superintendent = $this->cal_payments($remaining_amount, $superintendent_money);
        $administrator_money = $superintendent_money*(3/4);
        $remaining_after_admin = $this->cal_payments($remaining_after_superintendent, $administrator_money);


        $total_officers_general = $this->count_officers('officer');
        
        

        
        $general_officer_salary = $administrator_money*(8/5);
        $total_officer_salary = $general_officer_salary;
        if($total_officers_general){
           $total_officer_salary = $total_officers_general*$total_officer_salary;
        }
        
        //$general_officer_salary*$total_officers_general
        $remaining_after_general_officer_salary = $this->cal_payments($remaining_after_admin, $total_officer_salary);

        //echo $remaining_after_general_officer_salary;

        //total senior officers
        $senior_officer_salary = $general_officer_salary + $general_officer_salary*(6/100);
        $total_senior_officers = $this->count_officers('senior officer');
        //echo $total_senior_officers;
        if($total_senior_officers){
            $total_senior_officer_salary = $total_senior_officers*$senior_officer_salary;
        }        
        $remaining_amount_after_senior_officers = $this->
        cal_payments($remaining_after_general_officer_salary, $total_senior_officers);

        //calcu head officer
        $head_officer_salary = $general_officer_salary + $general_officer_salary*(3.5/100);

        //$general_officer_salary+=$all_officer_salary;
        //echo $general_officer_salary;


        $remaining_after_head_officer_bonus = $this->
        cal_payments($remaining_amount_after_senior_officers, $head_officer_salary);

        //echo $remaining_after_general_officer_bonus;

        //total money plus the bonus money

        $director_money_national_referal+=($remaining_after_head_officer_bonus*(5/100));
        $superintendent_total_salary = $director_money_national_referal/2;
        $admin_total_salary  = $superintendent_total_salary*(3/4);
        $officer_total_salary = $admin_total_salary*(8/5);
        $senior_total_salary = $officer_total_salary + $officer_total_salary*(6/100);
        $head_officer_total = $officer_total_salary + $officer_total_salary*(3.5/100);

        
        //   echo $head_officer_salary;
        //   echo $senior_total_salary;
        //   echo $director_money_national_referal;
        //updating records
        $this->update_payments($director_money_national_referal, 'director');
        $this->update_payments($superintendent_total_salary, 'superintendent');
        $this->update_payments($senior_total_salary, 'senior officer');
        $this->update_payments($officer_total_salary, 'officer');
        $this->update_payments($head_officer_salary, 'head');
        $this->update_payments_staff($director_money_national_referal, 'Director');
        $this->update_payments_staff($admin_total_salary, 'Admin');
        

          //return a view
          //$staff_money = $this->format_currency(DB::select("select role, name, monthly_allowane from users"));
          //formating the money
          $officers_at_general_hospitals = $this->format_currency($this->pass_to_format('general'));
           $officers_at_referal_hospitals = $this->format_currency($this->pass_to_format('regional'));
           $officers_at_national_hospitals = $this->format_currency($this->pass_to_format('national'));

           //staff members
          // $Admin = $this->$this->pass_to_format_staff($this->pass_to_format_staff('Admin'));

          $result = $this->pass_to_format_staff('Admin');

          $Admin = $this->format_currency_staff($result);
          //print_r($Admin3);
         
            //dd($officers_at_general_hospitals);
        
           
           return view('monthlypayment',
           [
           'officers_general'=>$officers_at_general_hospitals,
           'officers_regional'=>$officers_at_referal_hospitals,
           'officers_national'=>$officers_at_national_hospitals,
           'months'=>$this->all_months(),
           'default'=>$this->default_month,
           'staff'=>$Admin
           ]
        );
          

      }
      else{

           $officers_at_general_hospitals = array();
           $officers_at_referal_hospitals = array();
           $officers_at_national_hospitals = array();
           $Admin =  array();
         
        return view('monthlypayment',
        [
        'officers_general'=>$officers_at_general_hospitals,
        'officers_regional'=>$officers_at_referal_hospitals,
        'officers_national'=>$officers_at_national_hospitals,
        'months'=>$this->all_months(),
        'default'=>$this->default_month,
        'staff'=>$Admin
        ]
     );

      }
       
        

    }
}
