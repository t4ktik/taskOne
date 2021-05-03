<?php

namespace App;

use App\Mail\CommonEmailTemplate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class Utility extends Model
{
    // get Setting
    public static function settings()
    {
        $data = DB::table('settings')->where('created_by', '=', 1)->get();

        $settings = [
            "footer_text" => "© 2020 Pablo Orozco",
            "footer_link_1" => "Support",
            "footer_value_1" => "#",
            "footer_link_2" => "Terms",
            "footer_value_2" => "#",
            "footer_link_3" => "Privacy",
            "footer_value_3" => "#",
            "default_language" => "en",
            "enable_landing" => "on",
        ];

        foreach($data as $row)
        {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function getValByName($key)
    {
        $setting = self::settings();

        if(!isset($setting[$key]) || empty($setting[$key]))
        {
            $setting[$key] = '';
        }

        return $setting[$key];
    }

    // Get languages
    public static function languages()
    {
        $dir     = base_path() . '/resources/lang/';
        $glob    = glob($dir . "*", GLOB_ONLYDIR);
        $arrLang = array_map(
            function ($value) use ($dir){
                return str_replace($dir, '', $value);
            }, $glob
        );
        $arrLang = array_map(
            function ($value) use ($dir){
                return preg_replace('/[0-9]+/', '', $value);
            }, $arrLang
        );
        $arrLang = array_filter($arrLang);

        return $arrLang;
    }

    // Check File is exist and delete these
    public static function checkFileExistsnDelete(array $files)
    {
        $status = false;
        foreach($files as $key => $file)
        {
            if(Storage::exists($file))
            {
                $status = Storage::delete($file);
            }
        }

        return $status;
    }

    // Save Settings on .env file
    public static function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str     = file_get_contents($envFile);

        if(count($values) > 0)
        {
            foreach($values as $envKey => $envValue)
            {
                $keyPosition       = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine           = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if(!$keyPosition || !$endOfLinePosition || !$oldLine)
                {
                    $str .= "{$envKey}='{$envValue}'\n";
                }
                else
                {
                    $str = str_replace($oldLine, "{$envKey}='{$envValue}'", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        $str .= "\n";

        if(!file_put_contents($envFile, $str))
        {
            return false;
        }

        return true;
    }

    // for invoice number format
    public static function invoiceNumberFormat($number)
    {
        return '#' . sprintf("%05d", $number);
    }

    // get project wise currency formated amount
    public static function projectCurrencyFormat($project_id, $amount, $decimal = false)
    {
        $project = Project::find($project_id);
        if(empty($project))
        {
            $project                    = new Project();
            $project->currency          = '$';
            $project->currency_position = 'pre';
        }

        if($decimal == true)
        {
            $number = number_format($amount, 2);
        }
        else
        {
            $number = number_format($amount);
        }

        return (($project->currency_position == "pre") ? $project->currency : '') . $number . (($project->currency_position == "post") ? $project->currency : '');
    }

    // get progress bar color
    public static function getProgressColor($percentage)
    {
        $color = '';

        if($percentage <= 20)
        {
            $color = 'danger';
        }
        elseif($percentage > 20 && $percentage <= 40)
        {
            $color = 'warning';
        }
        elseif($percentage > 40 && $percentage <= 60)
        {
            $color = 'info';
        }
        elseif($percentage > 60 && $percentage <= 80)
        {
            $color = 'primary';
        }
        elseif($percentage >= 80)
        {
            $color = 'success';
        }

        return $color;
    }

    // get date formated
    public static function getDateFormated($date)
    {
        if(!empty($date) && $date != '0000-00-00')
        {
            return date("d M Y", strtotime($date));
        }
        else
        {
            return '';
        }
    }

    // Return timesheet sum of array
    public static function calculateTimesheetHours($times)
    {
        $minutes = 0;
        foreach($times as $time)
        {
            list($hour, $minute) = explode(':', $time);
            $minutes += $hour * 60;
            $minutes += $minute;
        }
        $hours   = floor($minutes / 60);
        $minutes -= $hours * 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    // Return multiple time to single total hr
    public static function timeToHr($times)
    {
        $totaltime = self::calculateTimesheetHours($times);
        $timeArray = explode(':', $totaltime);
        if($timeArray[1] <= '30')
        {
            $totaltime = $timeArray[0];
        }
        $totaltime = $totaltime != '00' ? $totaltime : '0';

        return $totaltime;
    }

    // Return Week first day and last day
    public static function getFirstSeventhWeekDay($week = null)
    {
        $first_day = $seventh_day = null;
        if(isset($week))
        {
            $first_day   = Carbon::now()->addWeeks($week)->startOfWeek();
            $seventh_day = Carbon::now()->addWeeks($week)->endOfWeek();
            //            $first_day   = Carbon::now()->addWeeks($week);
            //            $seventh_day = Carbon::now()->addWeeks($week + 1)->subDays(1);
        }
        $dateCollection['first_day']   = $first_day;
        $dateCollection['seventh_day'] = $seventh_day;
        $period                        = CarbonPeriod::create($first_day, $seventh_day);
        foreach($period as $key => $dateobj)
        {
            $dateCollection['datePeriod'][$key] = $dateobj;
        }

        return $dateCollection;
    }

    // Return Percentage from two value
    public static function getPercentage($val1 = 0, $val2 = 0)
    {
        $percentage = 0;
        if($val1 > 0 && $val2 > 0)
        {
            $percentage = intval(($val1 / $val2) * 100);
        }

        return $percentage;
    }

    // Return Last 7 Days with date & day name
    public static function getLastSevenDays()
    {
        $arrDuration   = [];
        $previous_week = strtotime("-1 week +1 day");

        for($i = 0; $i < 7; $i++)
        {
            $arrDuration[date('Y-m-d', $previous_week)] = date('D', $previous_week);
            $previous_week                              = strtotime(date('Y-m-d', $previous_week) . " +1 day");
        }

        return $arrDuration;
    }

    // Common Function That used to send mail with check all cases
    public static function sendEmailTemplate($emailTemplate, $mailTo, $obj, $project_id = 0)
    {
        $usr = Auth::user();

        //Remove Current Login user Email don't send mail to them
        unset($mailTo[$usr->id]);

        $mailTo = array_values($mailTo);

        if($usr->type != 'admin')
        {
            // find template is exist or not in our record
            $template = EmailTemplate::where('name', 'LIKE', $emailTemplate)->first();

            if(isset($template) && !empty($template))
            {
                // if project id not found then send mail without check
                if($project_id == 0)
                {
                    $is_active            = new ProjectEmailTemplate();
                    $is_active->is_active = 1;
                }
                else
                {
                    $is_active = ProjectEmailTemplate::where('template_id', '=', $template->id)->where('project_id', '=', $project_id)->first();
                }

                // check template is active or not by project
                if($is_active->is_active == 1)
                {
                    // get email content language base
                    $content       = EmailTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $usr->lang)->first();
                    $content->from = $template->from;

                    if(!empty($content->content))
                    {
                        $content->content = self::replaceVariable($content->content, $obj);

                        // send email
                        try
                        {
                            Mail::to($mailTo)->send(new CommonEmailTemplate($content));
                        }
                        catch(\Exception $e)
                        {
                            $error = __('E-Mail has been not sent due to SMTP configuration');
                        }

                        if(isset($error))
                        {
                            $arReturn = [
                                'is_success' => false,
                                'error' => $error,
                            ];
                        }
                        else
                        {
                            $arReturn = [
                                'is_success' => true,
                                'error' => false,
                            ];
                        }
                    }
                    else
                    {
                        $arReturn = [
                            'is_success' => false,
                            'error' => __('Mail not send, email is empty'),
                        ];
                    }

                    return $arReturn;
                }
                else
                {
                    return [
                        'is_success' => true,
                        'error' => false,
                    ];
                }
            }
            else
            {
                return [
                    'is_success' => false,
                    'error' => __('Mail not send, email not found'),
                ];
            }
        }
    }

    // used for replace email variable (parameter 'template_name','id(get particular record by id for data)')
    public static function replaceVariable($content, $obj)
    {
        $arrVariable = [
            '{project_name}',
            '{project_status}',
            '{project_budget}',
            '{project_hours}',
            '{task_name}',
            '{task_priority}',
            '{task_project}',
            '{task_stage}',
            '{timesheet_project}',
            '{timesheet_task}',
            '{timesheet_type}',
            '{timesheet_time}',
            '{timesheet_date}',
            '{app_name}',
            '{email}',
            '{password}',
            '{app_url}',
        ];
        $arrValue    = [
            'project_name' => '-',
            'project_status' => '-',
            'project_budget' => '-',
            'project_hours' => '-',
            'task_name' => '-',
            'task_priority' => '-',
            'task_project' => '-',
            'task_stage' => '-',
            'timesheet_project' => '-',
            'timesheet_task' => '-',
            'timesheet_type' => '-',
            'timesheet_time' => '-',
            'timesheet_date' => '-',
            'app_name' => '-',
            'email' => '-',
            'password' => '-',
            'app_url' => '-',
        ];

        foreach($obj as $key => $val)
        {
            $arrValue[$key] = $val;
        }

        $arrValue['app_name'] = env('APP_NAME');
        $arrValue['app_url']  = '<a href="' . env('APP_URL') . '" target="_blank">' . env('APP_URL') . '</a>';

        return str_replace($arrVariable, array_values($arrValue), $content);
    }

    // Make Entry in email_tempalte_lang table when create new language
    public static function makeEmailLang($lang)
    {
        $template = EmailTemplate::all();
        foreach($template as $t)
        {
            $default_lang                 = EmailTemplateLang::where('parent_id', '=', $t->id)->where('lang', 'LIKE', 'en')->first();
            $emailTemplateLang            = new EmailTemplateLang();
            $emailTemplateLang->parent_id = $t->id;
            $emailTemplateLang->lang      = $lang;
            $emailTemplateLang->subject   = $default_lang->subject;
            $emailTemplateLang->content   = $default_lang->content;
            $emailTemplateLang->save();
        }
    }
    // Email Template Modules Function END

    // For Invoice Template
    public static function templateData()
    {
        $arr              = [];
        $arr['colors']    = [
            '003580',
            '666666',
            '6777f0',
            'f50102',
            'f9b034',
            'fbdd03',
            'c1d82f',
            '37a4e4',
            '8a7966',
            '6a737b',
            '050f2c',
            '0e3666',
            '3baeff',
            '3368e6',
            'b84592',
            'f64f81',
            'f66c5f',
            'fac168',
            '46de98',
            '40c7d0',
            'be0028',
            '2f9f45',
            '371676',
            '52325d',
            '511378',
            '0f3866',
            '48c0b6',
            '297cc0',
            'ffffff',
            '000',
        ];
        $arr['templates'] = [
            "template1" => "New York",
            "template2" => "Toronto",
            "template3" => "Rio",
            "template4" => "London",
            "template5" => "Istanbul",
            "template6" => "Mumbai",
            "template7" => "Hong Kong",
            "template8" => "Tokyo",
            "template9" => "Sydney",
            "template10" => "Paris",
        ];

        return $arr;
    }

    // get font-color code accourding to bg-color
    public static function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if(strlen($hex) == 3)
        {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        }
        else
        {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array(
            $r,
            $g,
            $b,
        );

        //return implode(",", $rgb); // returns the rgb values separated by commas
        return $rgb; // returns an array with the rgb values
    }

    // For Font Color
    public static function getFontColor($color_code)
    {
        $rgb = self::hex2rgb($color_code);
        $R   = $G = $B = $C = $L = $color = '';

        $R = (floor($rgb[0]));
        $G = (floor($rgb[1]));
        $B = (floor($rgb[2]));

        $C = [
            $R / 255,
            $G / 255,
            $B / 255,
        ];

        for($i = 0; $i < count($C); ++$i)
        {
            if($C[$i] <= 0.03928)
            {
                $C[$i] = $C[$i] / 12.92;
            }
            else
            {
                $C[$i] = pow(($C[$i] + 0.055) / 1.055, 2.4);
            }
        }

        $L = 0.2126 * $C[0] + 0.7152 * $C[1] + 0.0722 * $C[2];

        if($L > 0.179)
        {
            $color = 'black';
        }
        else
        {
            $color = 'white';
        }

        return $color;
    }

    // For Delete Directory
    public static function delete_directory($dir)
    {
        if(!file_exists($dir))
        {
            return true;
        }

        if(!is_dir($dir))
        {
            return unlink($dir);
        }

        foreach(scandir($dir) as $item)
        {
            if($item == '.' || $item == '..')
            {
                continue;
            }

            if(!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item))
            {
                return false;
            }

        }

        return rmdir($dir);
    }

    // Function not used anywhere just create for translate some keyword language based.
    public function extraKeyword()
    {
        [
            __('Wed'),
            __('Tue'),
            __('Mon'),
            __('Sun'),
            __('Sat'),
            __('Fri'),
            __('Thu'),
            // User Type
            __('Owner'),
            __('Client'),
            __('User'),
            __('Shared'),
            // Project Status
            __('On Hold'),
            __('In Progress'),
            __('Complete'),
            __('Canceled'),
            // Project task Status
            __('Critical'),
            __('High'),
            __('Medium'),
            __('Low'),
            // Invoice Status
            __('Not Paid'),
            __('Partialy Paid'),
            __('Paid'),
            // Activity Log
            __('Invite User'),
            __('User Assigned to the Task'),
            __('User Removed from the Task'),
            __('Upload File'),
            __('Create Milestone'),
            __('Create Task'),
            __('Move Task'),
            __('Create Expense'),
            // Others
            __('Your favorite list is empty'),
        ];
    }

    // Get Messenger Migration
    public static function get_messenger_packages_migration()
    {
        $totalMigration = 0;
        $messengerPath  = glob(base_path() . '/vendor/munafio/chatify/database/migrations' . DIRECTORY_SEPARATOR . '*.php');
        if(!empty($messengerPath))
        {
            $messengerMigration = str_replace('.php', '', $messengerPath);
            $totalMigration     = count($messengerMigration);
        }

        return $totalMigration;
    }
}
