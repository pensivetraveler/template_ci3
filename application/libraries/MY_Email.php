<?php
if (!defined("BASEPATH")) exit("No direct script access allowed");

class MY_Email extends CI_Email
{
    protected function setEmailOptions($options = [])
    {
        $options = array_merge([
            'mailpath'  => '/usr/sbin/sendmail',
            'smtp_user' => getenv('MAIL_USERNAME'),
            'smtp_pass' => getenv('MAIL_PASSWORD'),
            'smtp_host' => getenv('MAIL_HOST'),
            'smtp_port' => getenv('MAIL_PORT'),
            'protocol'  => 'smtp',
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'wordWrap'  => true,
            'crlf'      => "\r\n",
            'newline'   => "\r\n",
            'encoding'  => 'base64',
        ], $options);

        if((int)$options['smtp_port'] === 465) {
            $options = array_merge($options, [
                'smtp_crypto' => 'tls'
            ]);
        }else{
            $options = array_merge($options, [
                'smtp_crypto' => 'ssl'
            ]);
        }

        $this->initialize($options);
    }

    protected function sendEmailTest($options, $dto = null)
    {
        $CI =& get_instance();

        try {
            $this->setEmailOptions($options);

            $this->from(getenv('MAIL_USERNAME'), getenv('APP_NAME_KR'));

            $to = is_empty($dto, 'to')?getenv('MAIL_USERNAME'):$dto['to'];
            $subject = is_empty($dto, 'subject')?'['.getenv('APP_NAME').']Test Email By '.date('Y-m-d H:i:s'):$dto['subject'];

            $message = 'Hello! This is a test email using <b>'.getenv('APP_NAME').'</b>.';
            if(!is_null($dto) && !is_empty($dto, 'template')) {
                $message = $CI->load->view("email/{$dto['template']}", $dto, true);
            }else if(!is_empty($dto, 'message')) {
                $message = $dto['message'];
            }

            $this->to($to);
            $this->subject($subject);
            $this->message($message);
            $this->set_alt_message('Hello! This is a test email using '.getenv('APP_NAME').'.');

            $this->send();
        } catch (Exception $e) {
            $debug_message = $this->print_debugger();


            echo json_encode([
                'email_type' => $dto['template'],
                'doc_id' => $dto['doc_id'],
                'email_address' => $dto['to'],
                'success_yn' => strlen($debug_message)>0?'N':'Y',
                'debug_message' => $debug_message,
            ]);
        }
    }
}