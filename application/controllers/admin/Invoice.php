<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Invoice extends CI_Controller {

    public $user_id;
    public $user_type;
    public $username;

    public function __construct() {
        parent::__construct();
        if (empty($this->session->userdata('admin'))) {
            redirect(base_url('login'));
        }
        $this->load->library("Pdf_obj");
        $this->user_id = $this->session->userdata('admin')->user_id;
        $this->user_type = $this->session->userdata('admin')->usertype;
        $this->username = $this->session->userdata('admin')->username;
        $this->lang->load('title', 'english');
        $this->load->model('Payment_Model', 'payment');
    }

    public function index() {
        $data['title'] = 'Dashboard';
        $this->load->view('template/admin/header', $data);
        $this->load->view('admin/Invoice_View');
        $this->load->view('template/admin/footer');
    }

    public function getPreviousMonthInvoice() {
        $invoices = $this->payment->getPreviousMonthInvoice($this->user_type, $this->user_id);
        $data = array();
        $no = $this->input->post('start');
        $i = 1;
        foreach ($invoices['data'] as $invoice) {
            $no++;
            $row = array();
            $row[] = $invoice->user_id;
            $row[] = $invoice->username;
            if ($invoice->emailid == '') {
                $row[] = 'Email not found';
            } else {
                $row[] = '<a href="' . base_url('admin/invoice/mail/') . $invoice->user_id . '_' . $invoice->From . '" />' . $invoice->emailid . '</a>';
            }
            $row[] = date('d-m-Y', strtotime($invoice->From));
            $row[] = date('d-m-Y', strtotime($invoice->To));
            $row[] = '<a href="' . base_url() . 'previous/invoice/' . $invoice->invoice_path . '"  target="_blank" />Invoice</a>';
            $row[] = '<a href="' . base_url() . 'previous/retail/' . $invoice->retail_path . '"  target="_blank" />Retail</a>';
            $row[] = '<a href="' . base_url('admin/invoice/mail/') . $invoice->user_id . '_' . $invoice->From . '" /><i class="fa fa-envelope-o"></i></a>';
            $data[] = $row;
            $i++;
        }
        $output = array(
            "draw" => $this->input->post('draw'),
            "recordsTotal" => $invoices['num_rows'],
            "recordsFiltered" => $invoices['filter'],
            "data" => $data,
        );
        echo json_encode($output);
    }

    public function mail($get) {
        $getid = explode('_', $get);
        $id = $getid[0];
        $date = $getid[1];
        $dateArray = explode('-', $date);
        $type = 'c';
        $invID = '';
        $check_invoice = $this->payment->checkInvoice($id, $dateArray[0] . '-' . $dateArray[1], $type);
        if ($check_invoice['status'] === true) {
            $invID = $check_invoice['result']->inv_no;
        }


        $Fromdate = date('Y-m-d', strtotime("first day of -1 month"));
        $Enddate = date('Y-m-d', strtotime("last day of -1 month"));
        // create new PDF document
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetFont('dejavusans', '', 10, '', true);
        // Add a page
        $pdf->AddPage('P', 'A4');

        $data = $this->payment->getPreviousUser($id);
        $details = $this->payment->getInvoicePreviousOrder($id, $Fromdate, $Enddate);
        $deliveryCharge = $this->payment->getDeliveryCharge($id, $Fromdate, $Enddate);

        // $userId = $row->user_id;
        $custName = $data->username;
//        $custEmail = $data->emailid;
        // $FromDate = $row->From;
        // $ToDate = $row->To;
        $address = $data->Address;
        $PayDate = $data->PayDate;
        $Telephone = '';
        if ($data->Telephone > 0) {
            $Telephone = $data->Telephone;
        }
        $zipcode = $data->Zipcode;
        $town = $data->town;
        $houseno = $data->house_no;
//            $floor = $row->floor;
        $cvr = $data->cvr_no;
        $html = '<table style="width:100%;border-collapse: collapse; padding:20px 15px;">
<tr style="background-color: #D3D3D3;">
    <td><img src="' . base_url() . 'assets/img/logo.png" alt="img" style="width:220px"/></td>
    <td align="left">
        <table style="width:100%;">
            <tbody>
                <tr>
                    <td align="right"><h3><strong>Ebager Danmark ApS</strong></h3></td>
                </tr>
                <tr>
                    <td align="right">Winthersmindevej 60</td>
                </tr>
                <tr>    
                    <td align="right">2635 Ishøj</td>
                </tr>
                <tr>
                    <td align="right">Tlf: +45 7027 3311</td>
                </tr>
                <tr>
                    <td align="right">E-mail: info@ebager.com</td>
                </tr>
                <tr>
                    <td align="right">CVR-nr.: 36 68 52 04</td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>
</table>
<table style="width:100%;border-collapse: collapse;padding:0px;" >
<!--1-->
<tbody>
    <tr> 
        <td style="width:5%;background-color: #D3D3D3;">&nbsp;</td>
        <td style="width:45%;background-color: #fff;border-top:4px solid #ccc;border-left:4px solid #ccc;">
            <table style="width:100%;border-collapse: collapse;" cellpadding="10">
                <tbody><tr><td><h3><strong>User Detail</strong></h3></td></tr></tbody>
            </table>
        </td>
        <td style="width:45%;background-color: #fff;border-top:4px solid #ccc;border-right:2px solid #ccc;" align="right">
            <table style="width:100%;border-collapse: collapse;" cellpadding="10">
                <tbody><tr><td align="right" ><h3><strong>Order Detail</strong></h3></td></tr></tbody>
            </table>
        </td>
        <td style="width:5%;background-color: #D3D3D3;">&nbsp;</td>
    </tr>
    <!--2nd-->
    <tr> 
        <td style="width:5.2%;">&nbsp;</td>
        <td style="width:44.8%;background-color: #fff;border-left:2px solid #ccc;">
            <table style="width:100%;border-right:1px solid #ccc;border-collapse: collapse;padding:0px 10px;">
                <tbody>
                    <tr><td>' . $custName . '</td></tr>
                    <tr><td>' . $address . ' ' . $houseno . '</td></tr>
                    <tr><td>' . $zipcode . ' ' . $town . '</td></tr>
                    <tr><td>Tlf: ' . $Telephone . '</td></tr>
                    <tr><td>CVRnr : ' . $cvr . '</td></tr>
                </tbody>
            </table>
        </td>
        <td style="width:45%;background-color: #fff; border-right:2px solid #ccc;" align="right">
            <table style="width:100%;border-collapse: collapse;padding:0px 10px;">
                <tbody>
                    <tr><td align="right">Faktura nr : ' . $invID . '</td></tr>
                    <tr><td align="right">Faktura dato : ' . date('Y-m-d') . '</td></tr>
                    <tr><td align="right">Nedenstående varer er leveret i tidsrummet</td></tr>
                    <tr><td align="right">' . date('d-m-Y', strtotime($Fromdate)) . ' til ' . date('d-m-Y', strtotime($Enddate)) . '</td></tr>
                </tbody>
            </table>
        </td>
        <td style="width:5%;">&nbsp;</td>
    </tr>
    <tr>
        <td style="width:5.2%;">&nbsp;</td>
        <td style="width:44.8%;background-color: #fff;border-left:2px solid #ccc;"></td>
        <td style="width:45%;background-color: #fff; border-right:2px solid #ccc;" align="right"></td>
        <td style="width:5%;">&nbsp;</td>
    </tr>
    <tr>
        <td style="width:5.2%;">&nbsp;</td>
        <td colspan="2" style="width:89.8%;border-left: 2px solid #ccc;border-right: 2px solid #ccc;">
            <table style="width: 98.6%;;padding:5px;" >
                <thead>
                    <tr style="background-color: #e5e2b5;">
                        <th style="border-right:2px solid #fff;text-align:center;">Antal</th>
                        <th style="border-right:2px solid #fff;text-align:left;">product</th>
                        <th style="border-right:2px solid #fff;text-align:right;">price</th>
                        <th align="right">total</th>
                    </tr>
                </thead>
                <tbody>';
        $amt = 0;
//        $delAmt = 0;
        $bgr = '';
        $i = 1;
        foreach ($details as $detail) {
            $cls = '';
            if ($i % 2 == 0) {
                $cls = 'style="background-color: #efecc9;"';
            }
            if ($detail->Bager != $bgr) {
                $html .='<tr ' . $cls . '><td colspan="4" align="center">' . $detail->Bager . '</td></tr>';
                $html .=' <tr ' . $cls . '>
                   <td align="center">' . $detail->qty . '</td>
                   <td>' . $detail->item . '</td>
                   <td align="right">' . number_format($detail->Price, 2, ',', '.') . '</td>
                   <td align="right">' . number_format($detail->qty * $detail->Price, 2, ',', '.') . '</td>
               </tr>';
                $amt = $amt + $detail->Total;
                $bgr = $detail->Bager;
                $i++;
            } else {
                $html .=' <tr ' . $cls . '>
                    <td align="center">' . $detail->qty . '</td>
                    <td>' . $detail->item . '</td>
                    <td align="right">' . number_format($detail->Price, 2, ',', '.') . '</td>
                    <td align="right">' . number_format($detail->qty * $detail->Price, 2, ',', '.') . '</td>
                </tr>';
                $amt = $amt + $detail->Total;
                $i++;
            }
        }
        $todel = $amt - $deliveryCharge->delivery;
        $delCh = abs($todel);
        $tamt = $amt + $delCh;
//        $tamt = $amt + $delAmt;
        $vat = ($tamt * 0.2);
        $html .='<tr><td colspan="4"></td></tr>
                        <tr style="background-color: #efecc9;">
                            <td colspan="3" align="right">Total varekøb :</td>
                            <td align="right">' . number_format($amt, 2, ',', '.') . '</td>
                        </tr> 
                        <tr style="background-color: #efecc9;">
                            <td colspan="2" align="center">Alle beløb nævnt er i DKK.</td>
                            <td align="right">Leveringsgebyr :</td>
                            <td align="right">' . number_format($tamt - $amt, 2, ',', '.') . '</td>
                        </tr>
                        <tr style="background-color: #efecc9;">
                            <td colspan="3" align="right">Total køb :</td>
                            <td align="right">' . number_format($tamt, 2, ',', '.') . '</td>
                        </tr> 
                        <tr><td colspan="4"></td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%">&nbsp;</td>
        </tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;background-color: #fff;border-left:2px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;">   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Heraf udgør moms 25% i alt: ' . number_format($vat, 2, ',', '.') . '
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>        
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;background-color: #fff;border-left:2px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;">   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Bedes indbetales på konto 3409 11615120 i Danske Bank senest den ' . date('d-m-Y', strtotime($PayDate)) . '.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>        
        <tr>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;" >   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Husk at angive indbetaler id: ' . $invID . ' og
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
        </tr>        
        <tr>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;" >   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                firmanavn: ' . $custName . '
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
        </tr>  
        <tr>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">&nbsp;</td>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
        </tr> 
         <tr>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;border-bottom:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;">   
                    <tbody>
                        <tr>
                            <td style="color:#ff0000;padding:15px;">
                                 Vi har fået nyt konto nr. pr. 30.09.16, Danske Bank 340911615120.
                            </td>
                        </tr>
                        <tr><td>&nbsp;</td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;background-color: #e5e2b5;">&nbsp;</td>
        </tr> 
        <tr><td style="background-color: #e5e2b5;" colspan="4">&nbsp;</td></tr>
        <tr><td style="background-color: #e5e2b5;" colspan="4">&nbsp;</td></tr>
        <tr><td style="background-color: #e5e2b5;" colspan="4">&nbsp;</td></tr>
    <tbody>
</table>';
        $merge = <<<EOD
$html
EOD;
        $pdf->writeHTML($merge);
//        $pdf->Output('Previous_month_invoice.pdf', 'D');
        $storepath = 'mail_pdf/' . date('d-m-Y') . '/';
        if (!is_dir($storepath)) {
            mkdir($storepath, 0777, true);
        }
        $pdf->Output('c:/ebager/' . $storepath . 'Previous_month_invoice.pdf', 'F');
        $send = $this->email->from('ebager@rayi.in', 'Ebager')
//                ->to($custEmail, $custName)
                ->to('l.jayamurugan@gmail.com', $custName)
                ->attach(base_url() . $storepath . '/Previous_month_invoice.pdf')
                ->subject('Ebager Balance')
                ->message('Hello ' . $custName . ', Balance List attached with this mail, please find it.')
                ->send();
        if ($send) {

            redirect(base_url('admin/Invoice'));
        }
    }

    public function invoice($get) {
        $getid = explode('_', $get);
        $cusID = $getid[0];
        $date = $getid[2];
        $Fromdate = date('Y-m-01', strtotime($date . ' -1 month'));
        $a_date = date('Y-m-01', strtotime($date . ' -1 month'));
        $Enddate = date("Y-m-t", strtotime($a_date));
        $cdate = explode('-', $Fromdate);
        $type = 'c';
        $invID = '';
        $check_invoice = $this->payment->checkInvoice($cusID, $cdate[0] . '-' . $cdate[1], $type);
        if ($check_invoice['status'] === true) {
            $invID = $check_invoice['result']->inv_no;
        }
        $data = $this->payment->getPreviousUser($cusID);
        $details = $this->payment->getInvoicePreviousOrder($cusID, $Fromdate, $Enddate);
        $deliveryCharge = $this->payment->getDeliveryCharge($cusID, $Fromdate, $Enddate);
        // create new PDF document
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetFont('dejavusans', '', 10, '', true);
        // Add a page
        $pdf->AddPage('P', 'A4');
        // $userId = $row->user_id;
        $custName = $data->username;
//        $custEmail = $data->emailid;
        // $FromDate = $row->From;
        // $ToDate = $row->To;
        $address = $data->Address;
        $PayDate = $data->PayDate;
        $Telephone = '';
        if ($data->Telephone > 0) {
            $Telephone = $data->Telephone;
        }
        $zipcode = $data->Zipcode;
        $town = $data->town;
        $houseno = $data->house_no;
//            $floor = $row->floor;
        $cvr = $data->cvr_no;
        $html = '<table style="width:100%;border-collapse: collapse; padding:20px 15px;">
    <tr style="background-color: #D3D3D3;">
        <td><img src="' . base_url() . 'assets/img/logo.png" alt="img" style="width:220px"/></td>
        <td align="left">
            <table style="width:100%;">
                <tbody style="float:right;">
                    <tr>
                        <td align="left"><h3><strong>Ebager Danmark ApS</strong></h3></td>
                    </tr>
                    <tr>
                        <td align="left">Winthersmindevej 60</td>
                    </tr>
                    <tr>    
                        <td align="left">2635 Ishøj</td>
                    </tr>
                    <tr>
                        <td align="left">Tlf: +45 7027 3311</td>
                    </tr>
                    <tr>
                        <td align="left">E-mail: info@ebager.com</td>
                    </tr>
                    <tr>
                        <td align="left">CVR-nr.: 36 68 52 04</td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>
<table style="width:100%;border-collapse: collapse;padding:0px;" >
    <!--1-->
    <tbody>
        <!--2nd-->
        <tr><td style="width:100%;">&nbsp;</td></tr>
        <tr> 
            <td style="width:5.2%;">&nbsp;</td>
            <td style="width:44.8%;background-color: #fff;border-left:2px solid #ccc;" align="left">
                <table style="width:100%;border-collapse: collapse;padding:0px 10px;">
                    <tbody>
                        <tr><td>' . $custName . '</td></tr>
                        <tr><td>' . $address . ' ' . $houseno . '</td></tr>
                        <tr><td>' . $zipcode . ' ' . $town . '</td></tr>
                        <tr><td>Tlf: ' . $Telephone . '</td></tr>
                        <tr><td>CVRnr : ' . $cvr . '</td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:45%;background-color: #fff; border-right:2px solid #ccc;" align="left">
                <table style="width:100%;border-collapse: collapse;padding:0px 10px;">
                    <tbody>
                        <tr><td align="left">Faktura nr. : ' . $invID . '</td></tr>
                        <tr><td align="left">Faktura dato :' . date('d-m-Y') . '</td></tr>
                        <tr><td align="left">Nedenstående varer er leveret i tidsrummet</td></tr>
                        <tr><td align="left">' . date('d-m-Y', strtotime($Fromdate)) . ' til ' . date('d-m-Y', strtotime($Enddate)) . '</td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td style="width:44.8%;background-color: #fff;border-left:2px solid #ccc;"></td>
            <td style="width:45%;background-color: #fff; border-right:2px solid #ccc;" align="right"></td>
            <td style="width:5%;">&nbsp;</td>
        </tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;border-left: 2px solid #ccc;border-right: 2px solid #ccc;">
                <table style="width: 98.6%;;padding:5px;" >
                    <thead>
                        <tr style="background-color: #D3D3D3;">
                            <th style="border-right:2px solid #fff;text-align:center;width:10%;">Antal</th>
                            <th style="border-right:2px solid #fff;text-align:left;width:50%;">product</th>
                            <th style="border-right:2px solid #fff;text-align:right;width:20%;">price</th>
                            <th align="right" style="width:20%;">total</th>
                        </tr>
                    </thead>
                    <tbody>';
        $amt = 0;
        $delAmt = 0;
        $bgr = '';
        $i = 1;
        foreach ($details as $detail) {
            $cls = '';
            if ($i % 2 == 0) {
                $cls = 'style="background-color: #D3D3D3;"';
            }
            if ($detail->Bager != $bgr) {
                $html .='<tr ' . $cls . '><td colspan="4" align="center">' . $detail->Bager . '</td></tr>';
                $html .=' <tr ' . $cls . '>
                   <td align="center" style="width:10%;">' . $detail->qty . '</td>
                   <td style="width:50%;">' . $detail->item . '</td>
                   <td align="right" style="width:20%;">' . number_format($detail->Price, 2, ',', '.') . '</td>
                   <td align="right" style="width:20%;">' . number_format($detail->qty * $detail->Price, 2, ',', '.') . '</td>
               </tr>';
                $amt = $amt + $detail->Total;
                $bgr = $detail->Bager;
                $i++;
            } else {
                $html .=' <tr ' . $cls . '>
                    <td align="center">' . $detail->qty . '</td>
                    <td>' . $detail->item . '</td>
                    <td align="right">' . number_format($detail->Price, 2, ',', '.') . '</td>
                    <td align="right">' . number_format($detail->qty * $detail->Price, 2, ',', '.') . '</td>
                </tr>';
                $amt = $amt + $detail->Total;
                $i++;
            }
        }
        $todel = $amt - $deliveryCharge->delivery;
        $delCh = abs($todel);
        $tamt = $amt + $delCh;
//        $tamt = $amt + $delAmt;
        $vat = ($tamt * 0.2);
        $html .='<tr><td colspan="4"></td></tr>
                        <tr style="background-color: #D3D3D3;">
                            <td colspan="3" align="right">Total varekøb :</td>
                            <td align="right">' . number_format($amt, 2, ',', '.') . '</td>
                        </tr> 
                        <tr style="background-color: #D3D3D3;">
                            <td colspan="2" align="center">Alle beløb nævnt er i DKK.</td>
                            <td align="right">Leveringsgebyr :</td>
                            <td align="right">' . number_format($tamt - $amt, 2, ',', '.') . '</td>
                        </tr>
                        <tr style="background-color: #D3D3D3;">
                            <td colspan="3" align="right">Total køb :</td>
                            <td align="right">' . number_format($tamt, 2, ',', '.') . '</td>
                        </tr> 
                        <tr><td colspan="4"></td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%">&nbsp;</td>
        </tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;background-color: #fff;border-left:2px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;">   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Heraf udgør moms 25% i alt: ' . number_format($vat, 2, ',', '.') . '
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>
        <tr><td></td></tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;background-color: #fff;border-left:2px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;">   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Bedes indbetales på konto 3409 11615120 i Danske Bank senest den ' . date('d-m-Y', strtotime($PayDate)) . '.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>        
        <tr>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;" >   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Husk at angive indbetaler id: ' . $invID . ' og
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
        </tr>        
        <tr>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;" >   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                firmanavn: ' . $custName . '
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
        </tr>  
        <tr>
            
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">&nbsp;</td>
            
        </tr> 
    <tbody>
</table>';
        $merge = <<<EOD
$html
EOD;
        $pdf->writeHTML($merge);
        $pdf->Output('Invoice.pdf', 'D');
    }

    public function retail($get) {
        $getid = explode('_', $get);
        $id = $getid[0];
        $date = $getid[2];
        $Fromdate = date('Y-m-01', strtotime($date . ' -1 month'));
        $a_date = date('Y-m-01', strtotime($date . ' -1 month'));
        $Enddate = date("Y-m-t", strtotime($a_date));
        $data = $this->payment->getPreviousUser($id);
        $details = $this->payment->getRetailPreviousUser($id, $Fromdate, $Enddate);
        $custName = $data->username;
          // create new PDF document
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetFont('dejavusans', '', 10, '', true);
        // Add a page
        $pdf->AddPage('P', 'A4');

        
        $htmlbody = '<table style="padding:20px;width:100%">
                        <tbody>
                            <tr>
                                <td>
                                    <table style="border-collapse: collapse;background-color:#FFFAE6";border:1px solid #000;>';

        $Ono = 0;
        $amt = 0;
        $stot = 0;
        $Gtot = 0;
        $top = 'T';
        foreach ($details as $detail) {

            if ($detail->OrderNo != $Ono && $top == 'T') {
                $top = 'Y';
                $stot = $stot + $detail->Total;
                $Gtot = $Gtot + $detail->Total;
                //$stot=$stot+$detail->Total;
                $htmlbody.='<tr width="100%" style="bordrer-bottom:1px solid black;">
                                <td width="100%" colspan="4" style="text-align:left;padding-left:0;">Ordernr.Nr.: ' . $detail->OrderNo . '<br>Leverings Dato:' . date('d-m-Y', strtotime($detail->DelivDate)) . '</td>
                            </tr>';
                $htmlbody.='<tr >
                                <td width="100%" colspan="4"></td>
                            </tr>';
                $htmlbody.='<tr >
                                <td align="left" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Antal &nbsp; </b></td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b> Beskrivelse </b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Pris &nbsp;</b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;"><b>Total &nbsp;</b></td>
                            </tr>';
                $htmlbody.='<tr>
                                <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . $detail->qty . ' &nbsp; </td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"> ' . $detail->item . '</td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . str_replace(".", ",", $detail->Price) . ' &nbsp; </td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">' . number_format($detail->Total, 2, ',', '') . ' &nbsp; </td></tr>';
                $Ono = $detail->OrderNo;
            } elseif ($detail->OrderNo != $Ono && $top == 'Y') {

                $htmlbody.='<tr >
                                <td align="right" width="100%" colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">Total:' . number_format($stot, 2, ',', '') . ' &nbsp; </td>
                            </tr>
                            <tr >
                                <td align="right"  colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;" > &nbsp; </td>
                            </tr>
                            <tr >
                                <td align="left" colspan="4">Ordernr.Nr.: ' . $detail->OrderNo . '<br>Leverings Dato:' . date('d-m-Y', strtotime($detail->DelivDate)) . '</td>
                            </tr>';
                $htmlbody.='<tr ><td ></td><td ></td><td></td><td></td></tr>';
                $htmlbody.='<tr >
                                <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Antal &nbsp; </b></td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b> Beskrivelse </b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Pris &nbsp;</b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;"><b>Total &nbsp;</b></td>
                            </tr>';
                $htmlbody.='<tr >
                                <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . $detail->qty . ' &nbsp; </td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"> ' . $detail->item . '</td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . str_replace(".", ",", $detail->Price) . ' &nbsp; </td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">' . number_format($detail->Total, 2, ',', '') . ' &nbsp; </td>
                            </tr>';
                $stot = $stot + $detail->Total;
                $Gtot = $Gtot + $detail->Total;
                //$delAmt=$delAmt+$detail->delivcharge;
                $Ono = $detail->OrderNo;
            } else {
                $stot = $stot + $detail->Total;
                $Gtot = $Gtot + $detail->Total;
                $htmlbody.='<tr >
                            <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . $detail->qty . ' &nbsp; </td>
                            <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"> ' . $detail->item . '</td>
                            <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . str_replace(".", ",", $detail->Price) . ' &nbsp; </td>
                            <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">' . number_format($detail->Total, 2, ',', '') . ' &nbsp; </td></tr>';
                $amt = $amt + $detail->Total;
                $Ono = $detail->OrderNo;
            }

            //$htmlbody=$htmlbody.$htmlbody;
        }
        $htmlbody.='<tr rowspan="2">
                        <td align="right" colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;" >Total:' . number_format($stot, 2, ',', '') . ' &nbsp;</td>
                    </tr>';
        $htmlbody.='<tr >
                        <td align="right" colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;" > &nbsp; </td>
                    </tr>';
        $htmlbody.='<tr >
                        <td align="right" colspan="4">Grand Total:' . number_format($Gtot, 2, ',', '') . '</td>
                    </tr>';
        $htmlbody.='<tr style="border:none;background-color:#fffae6"><td colspan="4" align="left" style="padding-left:0;"> Ebager Danmark ApS. Winthersmindevej 60 2635 Ishøj<br>Tlf.:+45 7027 3311 E-mail: info@ebager.dk<br>CVR-nr.: 36 68 52 04</td></tr>';
        $htmlbody.='</table></td></tr></tbody></table>';

// Set some content to print

        $html = '<thead style="padding:35px;"><h4><b>Kunde Navn : </b> ' . $custName . '</h4></thead>';

        $merge = $html . $htmlbody;
        $htmlfinal = <<<EOD
<p style="color:black;">$merge</p>
EOD;
        $pdf->writeHTMLCell(0, 0, '', '', $htmlfinal, 0, 1, 0, true, '', true);
        ob_end_clean();
        $pdf->Output('Retail_Invoice.pdf', 'D');
    }

    public function generatePreviousMonth() {
        $Fromdate = date('Y-m-d', strtotime("first day of -1 month"));
        $Enddate = date('Y-m-d', strtotime("last day of -1 month"));
        $customers = $this->payment->getPreviousUsers($Fromdate, $Enddate);
        $rs = $this->generateInvoice($customers);
        if ($rs['status'] == true) {
            $re = $this->generateRetail($customers);
            if ($re['status'] == true) {
                echo json_encode(array('status' => true, 'msg' => 'Generate Invoice and Retail invoice success'));
            } else {
                echo json_encode(array('status' => false, 'msg' => 'Generate Invoice success and Retail invoice failed'));
            }
        } else {
            echo json_encode(array('status' => false, 'msg' => 'Generate Invoice and Retail invoice failed'));
        }
    }

    private function generateInvoice($customers) {
        $Fromdate = date('Y-m-d', strtotime("first day of -1 month"));
        $Enddate = date('Y-m-d', strtotime("last day of -1 month"));
        $dateArray = explode('-', $Fromdate);
        foreach ($customers as $in_cus) {

            $type = 'c';
            $check_invoice = $this->payment->checkInvoice($in_cus->customerid, $dateArray[0] . '-' . $dateArray[1], $type);
            if ($check_invoice['status'] === true) {
                $invID = $check_invoice['result']->inv_no;
            }
            $users = $this->payment->getCustomerDetails($in_cus->customerid);
            $orders = $this->payment->getOrdrDetail($in_cus->customerid, $Fromdate, $Enddate);
            $delivery_charge = $this->payment->getDeliveryChargeInvoice($in_cus->customerid, $Fromdate, $Enddate);
            $cntPerson = $users->contact_person;
            $cvr = $users->cvr_no;
            $telephone = $users->telephone;
            $zipcode = $users->zipcode;
            $town = $users->town;
            $houseno = $users->house_no;
            $invoice_note = $users->invoice_note;
            $ean_code = $users->ean_code;

            if (!empty($telephone)) {
                $telephone = '<tr><td>Tiff : ' . $telephone . '</td></tr>';
            }
            if (!empty($cvr)) {
                $cvr = '<tr><td>CVRnr : ' . $cvr . '</td></tr>';
            }
            if (!empty($ean_code)) {
                $ean_code = '<tr><td>EAN kode : ' . $ean_code . '</td></tr>';
            }if (!empty($invoice_note)) {
                $invoice_note = '<tr><td>Faktura note : ' . $invoice_note . '</td></tr>';
            }if (!empty($cntPerson)) {
                $cntPerson = '<tr><td><strong>Att</strong> : ' . $cntPerson . '</td></tr>';
            }

            $pdf = new TCPDF();
            $pdf->setPrintHeader(false);
            $pdf->setCellPaddings(0, 0, 0, 0);
            $pdf->SetMargins(0, 0, 0, true);
            $pdf->SetFont('dejavusans', '', 10, '', true);
            // Add a page
            $pdf->AddPage('P', 'A4');
            $html = '<table style="width:100%;border-collapse: collapse; padding:20px 15px;">
    <tr style="background-color: #D3D3D3;">
        <td><img src="' . base_url() . 'assets/img/logo.png" alt="img" style="width:220px"/></td>
        <td align="left">
            <table style="width:100%;">
                <tbody style="float:right;">
                    <tr>
                        <td align="left"><h3><strong>Ebager Danmark ApS</strong></h3></td>
                    </tr>
                    <tr>
                        <td align="left">Winthersmindevej 60</td>
                    </tr>
                    <tr>    
                        <td align="left">2635 Ishøj</td>
                    </tr>
                    <tr>
                        <td align="left">Tlf: +45 7027 3311</td>
                    </tr>
                    <tr>
                        <td align="left">E-mail: info@ebager.com</td>
                    </tr>
                    <tr>
                        <td align="left">CVR-nr.: 36 68 52 04</td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>
<table style="width:100%;border-collapse: collapse;padding:0px;" >
    <!--1-->
    <tbody>
        <!--2nd-->
        <tr><td style="width:100%;">&nbsp;</td></tr>
        <tr> 
            <td style="width:5.2%;">&nbsp;</td>
            <td style="width:44.8%;background-color: #fff;border-left:2px solid #ccc;" align="left">
                <table style="width:100%;border-collapse: collapse;padding:0px 10px;">
                    <tbody>
                        <tr><td>' . $custName . '</td></tr>
                        <tr><td>' . $address . ' ' . $houseno . '</td></tr>
                        <tr><td>' . $zipcode . ' ' . $town . '</td></tr>
                        <tr><td>Tlf: ' . $Telephone . '</td></tr>
                        <tr><td>CVRnr : ' . $cvr . '</td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:45%;background-color: #fff; border-right:2px solid #ccc;" align="left">
                <table style="width:100%;border-collapse: collapse;padding:0px 10px;">
                    <tbody>
                        <tr><td align="left">Faktura nr. : ' . $invID . '</td></tr>
                        <tr><td align="left">Faktura dato :' . date('d-m-Y') . '</td></tr>
                        <tr><td align="left">Nedenstående varer er leveret i tidsrummet</td></tr>
                        <tr><td align="left">' . date('d-m-Y', strtotime($Fromdate)) . ' til ' . date('d-m-Y', strtotime($Enddate)) . '</td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td style="width:44.8%;background-color: #fff;border-left:2px solid #ccc;"></td>
            <td style="width:45%;background-color: #fff; border-right:2px solid #ccc;" align="right"></td>
            <td style="width:5%;">&nbsp;</td>
        </tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;border-left: 2px solid #ccc;border-right: 2px solid #ccc;">
                <table style="width: 98.6%;;padding:5px;" >
                    <thead>
                        <tr style="background-color: #D3D3D3;">
                            <th style="border-right:2px solid #fff;text-align:center;width:10%;">Antal</th>
                            <th style="border-right:2px solid #fff;text-align:left;width:50%;">product</th>
                            <th style="border-right:2px solid #fff;text-align:right;width:20%;">price</th>
                            <th align="right" style="width:20%;">total</th>
                        </tr>
                    </thead>
                    <tbody>';

            $bgr = 'style="background-color: #FFFAE6;"';
//        $delivery_charge = 0;
            $total = 0;
            $i = 0;
            foreach ($orders as $row) {
                $cls = '';
                if ($i % 2 == 0) {
                    $cls = 'style="background-color: #D3D3D3;"';
                }
                if ($row->bager != $bgr) {
                    $html .='<tr ' . $cls . '><td colspan="4" align="center">' . $detail->Bager . '</td></tr>';
                $html .=' <tr ' . $cls . '>
                   <td align="center" style="width:10%;">' . $detail->qty . '</td>
                   <td style="width:50%;">' . $detail->item . '</td>
                   <td align="right" style="width:20%;">' . number_format($detail->Price, 2, ',', '.') . '</td>
                   <td align="right" style="width:20%;">' . number_format($detail->qty * $detail->Price, 2, ',', '.') . '</td>
               </tr>';
                    $total = $total + ($row->price * $row->qty);
//                $delivery_charge = $delivery_charge + $row->delivery_charge;
                    $bgr = $row->bager;
                    $i++;
                } else {
                     $html .=' <tr ' . $cls . '>
                    <td align="center">' . $detail->qty . '</td>
                    <td>' . $detail->item . '</td>
                    <td align="right">' . number_format($detail->Price, 2, ',', '.') . '</td>
                    <td align="right">' . number_format($detail->qty * $detail->Price, 2, ',', '.') . '</td>
                </tr>';
                    $total = $total + ($row->price * $row->qty);
//                $delivery_charge = $delivery_charge + $row->delivery_charge;
                    $i++;
                }
            }
            $grand_total = $total + $delivery_charge;
            $vat = $grand_total * 0.2;
            $html .='<tr><td colspan="4"></td></tr>
                        <tr style="background-color: #D3D3D3;">
                            <td colspan="3" align="right">Total varekøb :</td>
                            <td align="right">' . number_format($amt, 2, ',', '.') . '</td>
                        </tr> 
                        <tr style="background-color: #D3D3D3;">
                            <td colspan="2" align="center">Alle beløb nævnt er i DKK.</td>
                            <td align="right">Leveringsgebyr :</td>
                            <td align="right">' . number_format($tamt - $amt, 2, ',', '.') . '</td>
                        </tr>
                        <tr style="background-color: #D3D3D3;">
                            <td colspan="3" align="right">Total køb :</td>
                            <td align="right">' . number_format($tamt, 2, ',', '.') . '</td>
                        </tr> 
                        <tr><td colspan="4"></td></tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%">&nbsp;</td>
        </tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;background-color: #fff;border-left:2px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;">   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Heraf udgør moms 25% i alt: ' . number_format($vat, 2, ',', '.') . '
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>
        <tr><td></td></tr>
        <tr>
            <td style="width:5.2%;">&nbsp;</td>
            <td colspan="2" style="width:89.8%;background-color: #fff;border-left:2px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;">   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Bedes indbetales på konto 3409 11615120 i Danske Bank senest den ' . date('d-m-Y', strtotime($PayDate)) . '.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;">&nbsp;</td>
        </tr>        
        <tr>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;" >   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                Husk at angive indbetaler id: ' . $invID . ' og
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
        </tr>        
        <tr>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">
                <table style="width:100%;border-collapse: collapse;" >   
                    <tbody>
                        <tr>
                            <td style="padding:15px;">
                                firmanavn: ' . $custName . '
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width:5%;background-color: #fff;">&nbsp;</td>
        </tr>  
        <tr>
            
            <td colspan="2" style="width:90%;background-color: #fff;border-left:4px solid #ccc;border-right:2px solid #ccc;">&nbsp;</td>
            
        </tr> 
    <tbody>
</table>';
            $merge = <<<EOD
$html
EOD;
//        print_r($merge);exit;
// output the HTML content
            $pdf->writeHTML($merge);
//            $pdf->Output('Previous_month_invoice.pdf', 'D');
            $uploadPath = APPPATH . '../previous/invoice/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, TRUE);
            }
            $pdf->Output($uploadPath . $in_cus->customerid . '_invoice.pdf', 'F');
            $in_re_data = 'invoice_path ="' . $in_cus->customerid . '_invoice.pdf"';
            $this->payment->updateInvoiceRetail($invID, $in_re_data);
        }
        return array('status' => true);
    }

    private function generateRetail($customers) {
        $Fromdate = date('Y-m-d', strtotime("first day of -1 month"));
        $Enddate = date('Y-m-d', strtotime("last day of -1 month"));
        $dateArray = explode('-', $Fromdate);
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Muhammad Saqlain Arif');
        $pdf->SetTitle('eBager Invoice');
        $pdf->SetSubject('Invoice for 01-Feb-2017 to 28-Feb-2017');
        $pdf->SetKeywords('Copy rights eBager');

        // set default header data
        //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
        //$pdf->setFooterData(array(0,64,0), array(0,64,128)); 
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------    
        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        // Set font
        // dejavusans is a UTF-8 Unicode font, if you only need to
        // print standard ASCII chars, you can use core fonts like
        // helvetica or times to reduce file size.
        $pdf->SetFont('dejavusans', '', 10, '', true);

        // Add a page
        // This method has several options, check the source code documentation for more information.
        $pdf->AddPage();

        // set text shadow effect
        $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.2, 'depth_h' => 0.2, 'color' => array(196, 196, 196), 'opacity' => 1, 'blend_mode' => 'Normal'));
        foreach ($customers as $in_cus) {
            $type = 'c';
            $check_invoice = $this->payment->checkInvoice($in_cus->customerid, $dateArray[0] . '-' . $dateArray[1], $type);
            if ($check_invoice['status'] === true) {
                $invID = $check_invoice['result']->inv_no;
            }
            $data = $this->payment->getPreviousUser($in_cus->customerid);
            $details = $this->payment->getRetailPreviousUser($in_cus->customerid, $Fromdate, $Enddate);
            $custName = $data->username;
            // create new PDF document
            $pdf = new TCPDF();
            $pdf->setPrintHeader(false);
            $pdf->setCellPaddings(0, 0, 0, 0);
            $pdf->SetMargins(0, 0, 0, true);
            $pdf->SetFont('dejavusans', '', 10, '', true);
            // Add a page
            $pdf->AddPage('P', 'A4');

            $htmlbody = '<table style="padding:20px;width:100%">
                        <tbody>
                            <tr>
                                <td>
                                    <table style="border-collapse: collapse;background-color:#FFFAE6";border:1px solid #000;>';

            $Ono = 0;
            $amt = 0;
            $stot = 0;
            $Gtot = 0;
            $top = 'T';
            foreach ($details as $detail) {

                if ($detail->OrderNo != $Ono && $top == 'T') {
                    $top = 'Y';
                    $stot = $stot + $detail->Total;
                    $Gtot = $Gtot + $detail->Total;
                    //$stot=$stot+$detail->Total;
                    $htmlbody.='<tr width="100%" style="bordrer-bottom:1px solid black;">
                                <td width="100%" colspan="4" style="text-align:left;padding-left:0;">Ordernr.Nr.: ' . $detail->OrderNo . '<br>Leverings Dato:' . date('d-m-Y', strtotime($detail->DelivDate)) . '</td>
                            </tr>';
                    $htmlbody.='<tr >
                                <td width="100%" colspan="4"></td>
                            </tr>';
                    $htmlbody.='<tr >
                                <td align="left" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Antal &nbsp; </b></td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b> Beskrivelse </b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Pris &nbsp;</b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;"><b>Total &nbsp;</b></td>
                            </tr>';
                    $htmlbody.='<tr>
                                <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . $detail->qty . ' &nbsp; </td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"> ' . $detail->item . '</td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . str_replace(".", ",", $detail->Price) . ' &nbsp; </td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">' . number_format($detail->Total, 2, ',', '') . ' &nbsp; </td></tr>';
                    $Ono = $detail->OrderNo;
                } elseif ($detail->OrderNo != $Ono && $top == 'Y') {

                    $htmlbody.='<tr >
                                <td align="right" width="100%" colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">Total:' . number_format($stot, 2, ',', '') . ' &nbsp; </td>
                            </tr>
                            <tr >
                                <td align="right"  colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;" > &nbsp; </td>
                            </tr>
                            <tr >
                                <td align="left" colspan="4">Ordernr.Nr.: ' . $detail->OrderNo . '<br>Leverings Dato:' . date('d-m-Y', strtotime($detail->DelivDate)) . '</td>
                            </tr>';
                    $htmlbody.='<tr ><td ></td><td ></td><td></td><td></td></tr>';
                    $htmlbody.='<tr >
                                <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Antal &nbsp; </b></td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b> Beskrivelse </b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"><b>Pris &nbsp;</b></td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;"><b>Total &nbsp;</b></td>
                            </tr>';
                    $htmlbody.='<tr >
                                <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . $detail->qty . ' &nbsp; </td>
                                <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"> ' . $detail->item . '</td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . str_replace(".", ",", $detail->Price) . ' &nbsp; </td>
                                <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">' . number_format($detail->Total, 2, ',', '') . ' &nbsp; </td>
                            </tr>';
                    $stot = $stot + $detail->Total;
                    $Gtot = $Gtot + $detail->Total;
                    //$delAmt=$delAmt+$detail->delivcharge;
                    $Ono = $detail->OrderNo;
                } else {
                    $stot = $stot + $detail->Total;
                    $Gtot = $Gtot + $detail->Total;
                    $htmlbody.='<tr >
                            <td align="right" width="10%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . $detail->qty . ' &nbsp; </td>
                            <td align="left" width="50%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;"> ' . $detail->item . '</td>
                            <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;border-right:0.1px solid black;">' . str_replace(".", ",", $detail->Price) . ' &nbsp; </td>
                            <td align="right" width="20%" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;">' . number_format($detail->Total, 2, ',', '') . ' &nbsp; </td></tr>';
                    $amt = $amt + $detail->Total;
                    $Ono = $detail->OrderNo;
                }

                //$htmlbody=$htmlbody.$htmlbody;
            }
            $htmlbody.='<tr rowspan="2">
                        <td align="right" colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;" >Total:' . number_format($stot, 2, ',', '') . ' &nbsp;</td>
                    </tr>';
            $htmlbody.='<tr >
                        <td align="right" colspan="4" style="border-top-color:#000000;border-top-width:1px;border-top-style:solid; 1px solid black;" > &nbsp; </td>
                    </tr>';
            $htmlbody.='<tr >
                        <td align="right" colspan="4">Grand Total:' . number_format($Gtot, 2, ',', '') . '</td>
                    </tr>';
            $htmlbody.='<tr style="border:none;background-color:#fffae6"><td colspan="4" align="left" style="padding-left:0;"> Ebager Danmark ApS. Winthersmindevej 60 2635 Ishøj<br>Tlf.:+45 7027 3311 E-mail: info@ebager.dk<br>CVR-nr.: 36 68 52 04</td></tr>';
            $htmlbody.='</table></td></tr></tbody></table>';

// Set some content to print

            $html = '<thead style="padding:35px;"><h4><b>Kunde Navn : </b> ' . $custName . '</h4></thead>';

            $merge = $html . $htmlbody;
            $htmlfinal = <<<EOD
<p style="color:black;">$merge</p>
EOD;
            $pdf->writeHTMLCell(0, 0, '', '', $htmlfinal, 0, 1, 0, true, '', true);

            // ---------------------------------------------------------    
            // ob_end_clean();
            $uploadPath = APPPATH . '../previous/retail/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, TRUE);
            }
            $pdf->Output($uploadPath . $in_cus->customerid . '_retail.pdf', 'F');
            $in_re_data = 'retail_path ="' . $in_cus->customerid . '_retail.pdf"';
            $this->payment->updateInvoiceRetail($invID, $in_re_data);
            // $storepath = 'invoices/pdfs/' . date('d-m-Y') . '/';
            // if (!is_dir($storepath)) {
            // mkdir($storepath, 0777, true);
            // }
            // Close and output PDF document
            // This method has several options, check the source code documentation for more information.
            // $pdf->Output('/home2/rayiipsb/public_html/ebager_dev/' . $storepath . '_Retail_Invoice.pdf', 'F');
            // $pdf->Output('Previous_Month_Retail_Invoice.pdf', 'D');
            // /* */
            // ============================================================+
            // END OF FILE
            // ============================================================+
        }
        return array('status' => true);
    }

}
