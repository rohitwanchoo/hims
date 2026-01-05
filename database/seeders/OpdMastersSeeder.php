<?php

namespace Database\Seeders;

use App\Models\ReferenceDoctor;
use App\Models\Client;
use App\Models\PatientClass;
use App\Models\CashlessPriceList;
use App\Models\WardWiseCostAddition;
use App\Models\PaymentMode;
use App\Models\PaymentModeDetail;
use App\Models\CancelReason;
use App\Models\Vaccination;
use App\Models\Service;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class OpdMastersSeeder extends Seeder
{
    public function run(): void
    {
        // Create Reference Doctors
        $referenceDoctors = [
            [
                'doctor_code' => 'REF001',
                'full_name' => 'Dr. Rajesh Kumar',
                'qualification' => 'MBBS, MD (Medicine)',
                'skill_set' => 'General Medicine',
                'registration_no' => 'MCI-12345',
                'hospital_name' => 'City Medical Center',
                'group_name' => 'City Doctors Group',
                'address' => '45 Medical Lane',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'mobile' => '9876543210',
                'email' => 'rajesh.kumar@email.com',
                'commission_percent' => 10.00,
            ],
            [
                'doctor_code' => 'REF002',
                'full_name' => 'Dr. Priya Sharma',
                'qualification' => 'MBBS, MS (Gynecology)',
                'skill_set' => 'Gynecology & Obstetrics',
                'registration_no' => 'MCI-23456',
                'hospital_name' => 'Women\'s Care Hospital',
                'group_name' => 'Women Health Associates',
                'address' => '78 Health Street',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'pincode' => '110001',
                'mobile' => '9876543211',
                'email' => 'priya.sharma@email.com',
                'commission_percent' => 12.00,
            ],
            [
                'doctor_code' => 'REF003',
                'full_name' => 'Dr. Amit Patel',
                'qualification' => 'MBBS, DM (Cardiology)',
                'skill_set' => 'Cardiology',
                'registration_no' => 'MCI-34567',
                'hospital_name' => 'Heart Care Institute',
                'group_name' => 'Cardiac Specialists',
                'address' => '23 Heart Avenue',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'pincode' => '380001',
                'mobile' => '9876543212',
                'email' => 'amit.patel@email.com',
                'commission_percent' => 15.00,
            ],
            [
                'doctor_code' => 'REF004',
                'full_name' => 'Dr. Sunita Reddy',
                'qualification' => 'MBBS, MD (Pediatrics)',
                'skill_set' => 'Pediatrics',
                'registration_no' => 'MCI-45678',
                'hospital_name' => 'Child Care Clinic',
                'group_name' => 'Pediatric Care Group',
                'address' => '56 Kids Lane',
                'city' => 'Hyderabad',
                'state' => 'Telangana',
                'pincode' => '500001',
                'mobile' => '9876543213',
                'email' => 'sunita.reddy@email.com',
                'commission_percent' => 8.00,
            ],
            [
                'doctor_code' => 'REF005',
                'full_name' => 'Dr. Vikram Singh',
                'qualification' => 'MBBS, MS (Orthopedics)',
                'skill_set' => 'Orthopedics & Joint Replacement',
                'registration_no' => 'MCI-56789',
                'hospital_name' => 'Bone & Joint Hospital',
                'group_name' => 'Orthopedic Surgeons Association',
                'address' => '89 Bone Street',
                'city' => 'Jaipur',
                'state' => 'Rajasthan',
                'pincode' => '302001',
                'mobile' => '9876543214',
                'email' => 'vikram.singh@email.com',
                'commission_percent' => 12.00,
            ],
        ];

        foreach ($referenceDoctors as $doctor) {
            ReferenceDoctor::create($doctor);
        }

        // Create Clients (TPA/Insurance/Corporate)
        $clients = [
            [
                'client_code' => 'TPA001',
                'client_name' => 'ICICI Lombard Health Insurance',
                'contact_person' => 'Mr. Ramesh Gupta',
                'ledger_name' => 'ICICI Lombard Ledger',
                'address' => 'ICICI Lombard House, 414 Veer Savarkar Marg',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400025',
                'mobile' => '9800000001',
                'phone' => '022-44556677',
                'email' => 'claims@icicilombard.com',
                'website' => 'www.icicilombard.com',
                'category' => 'insurance',
                'rate_based_on' => 'cashless_price_list',
                'discount_percent' => 0,
                'credit_limit' => 5000000,
                'credit_days' => 45,
            ],
            [
                'client_code' => 'TPA002',
                'client_name' => 'Star Health Insurance',
                'contact_person' => 'Ms. Lakshmi Iyer',
                'ledger_name' => 'Star Health Ledger',
                'address' => '15, Sri Ram Nagar, North Usman Road',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600017',
                'mobile' => '9800000002',
                'phone' => '044-33445566',
                'email' => 'claims@starhealth.in',
                'website' => 'www.starhealth.in',
                'category' => 'insurance',
                'rate_based_on' => 'decrease',
                'rate_adjustment_percent' => 10,
                'discount_percent' => 0,
                'credit_limit' => 3000000,
                'credit_days' => 30,
            ],
            [
                'client_code' => 'CORP001',
                'client_name' => 'Tata Consultancy Services',
                'contact_person' => 'Mr. Suresh Menon',
                'ledger_name' => 'TCS Corporate Ledger',
                'address' => 'TCS House, Raveline Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'mobile' => '9800000003',
                'phone' => '022-66778899',
                'email' => 'medical@tcs.com',
                'website' => 'www.tcs.com',
                'category' => 'corporate',
                'rate_based_on' => 'normal',
                'discount_percent' => 15,
                'credit_limit' => 2000000,
                'credit_days' => 30,
            ],
            [
                'client_code' => 'CORP002',
                'client_name' => 'Infosys Limited',
                'contact_person' => 'Ms. Anita Desai',
                'ledger_name' => 'Infosys Corporate Ledger',
                'address' => 'Electronics City',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'pincode' => '560100',
                'mobile' => '9800000004',
                'phone' => '080-22334455',
                'email' => 'medical@infosys.com',
                'website' => 'www.infosys.com',
                'category' => 'corporate',
                'rate_based_on' => 'normal',
                'discount_percent' => 12,
                'credit_limit' => 1500000,
                'credit_days' => 30,
            ],
            [
                'client_code' => 'GOV001',
                'client_name' => 'CGHS - Central Government Health Scheme',
                'contact_person' => 'Dr. S.K. Verma',
                'ledger_name' => 'CGHS Ledger',
                'address' => 'Nirman Bhawan',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'pincode' => '110011',
                'mobile' => '9800000005',
                'phone' => '011-23061234',
                'email' => 'cghs@gov.in',
                'website' => 'www.cghs.gov.in',
                'category' => 'government',
                'rate_based_on' => 'cashless_price_list',
                'discount_percent' => 0,
                'credit_limit' => 10000000,
                'credit_days' => 60,
            ],
            [
                'client_code' => 'TRUST001',
                'client_name' => 'Rotary Club Health Trust',
                'contact_person' => 'Mr. Ashok Mehta',
                'ledger_name' => 'Rotary Trust Ledger',
                'address' => 'Rotary House, 45 MG Road',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
                'mobile' => '9800000006',
                'phone' => '020-25678901',
                'email' => 'health@rotarypune.org',
                'website' => 'www.rotarypune.org',
                'category' => 'trust',
                'rate_based_on' => 'decrease',
                'rate_adjustment_percent' => 25,
                'discount_percent' => 0,
                'credit_limit' => 500000,
                'credit_days' => 15,
            ],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }

        // Create Patient Classes
        $classes = [
            [
                'class_code' => 'GEN',
                'class_name' => 'General',
                'description' => 'General cash paying patients with standard hospital tariff',
                'is_cashless' => false,
                'is_copay' => false,
                'cashless_applicable_on' => 'both',
            ],
            [
                'class_code' => 'ICICI-CL',
                'class_name' => 'ICICI Lombard Cashless',
                'description' => 'ICICI Lombard cashless patients',
                'client_id' => 1,
                'discount_ledger' => 'ICICI Discount Ledger',
                'is_cashless' => true,
                'apply_service_charges_on_cashless' => true,
                'cashless_applicable_on' => 'both',
                'ipd_for_new' => true,
                'prior_approval_required' => true,
                'prior_approval_limit' => 50000,
            ],
            [
                'class_code' => 'STAR-CL',
                'class_name' => 'Star Health Cashless',
                'description' => 'Star Health cashless patients',
                'client_id' => 2,
                'discount_ledger' => 'Star Health Discount Ledger',
                'is_cashless' => true,
                'apply_service_charges_on_cashless' => true,
                'cashless_applicable_on' => 'both',
                'ipd_for_new' => true,
                'prior_approval_required' => true,
                'prior_approval_limit' => 75000,
            ],
            [
                'class_code' => 'ICICI-RE',
                'class_name' => 'ICICI Lombard Reimbursement',
                'description' => 'ICICI Lombard reimbursement patients - pay first, claim later',
                'client_id' => 1,
                'is_cashless' => true,
                'is_cashless_reimbursement' => true,
                'cashless_applicable_on' => 'both',
            ],
            [
                'class_code' => 'TCS-CORP',
                'class_name' => 'TCS Corporate',
                'description' => 'TCS corporate employees',
                'client_id' => 3,
                'discount_ledger' => 'TCS Discount Ledger',
                'is_cashless' => false,
                'cashless_applicable_on' => 'both',
            ],
            [
                'class_code' => 'INFY-CORP',
                'class_name' => 'Infosys Corporate',
                'description' => 'Infosys corporate employees',
                'client_id' => 4,
                'discount_ledger' => 'Infosys Discount Ledger',
                'is_cashless' => false,
                'cashless_applicable_on' => 'both',
            ],
            [
                'class_code' => 'CGHS-CL',
                'class_name' => 'CGHS Cashless',
                'description' => 'Central Government Health Scheme beneficiaries',
                'client_id' => 5,
                'discount_ledger' => 'CGHS Discount Ledger',
                'is_cashless' => true,
                'apply_service_charges_on_cashless' => false,
                'cashless_applicable_on' => 'both',
                'ipd_for_new' => true,
                'service_tax_applicable' => false,
            ],
            [
                'class_code' => 'COPAY-50',
                'class_name' => 'Co-Pay 50:50',
                'description' => 'Co-pay class where patient and TPA share 50:50',
                'client_id' => 2,
                'is_cashless' => true,
                'is_copay' => true,
                'copay_patient_percent' => 50,
                'copay_tpa_percent' => 50,
                'cashless_applicable_on' => 'both',
            ],
            [
                'class_code' => 'COPAY-70',
                'class_name' => 'Co-Pay 70:30',
                'description' => 'Co-pay class where TPA pays 70%, patient pays 30%',
                'client_id' => 1,
                'is_cashless' => true,
                'is_copay' => true,
                'copay_patient_percent' => 30,
                'copay_tpa_percent' => 70,
                'cashless_applicable_on' => 'both',
            ],
            [
                'class_code' => 'ROTARY-TR',
                'class_name' => 'Rotary Trust Beneficiary',
                'description' => 'Rotary Trust health scheme beneficiaries',
                'client_id' => 6,
                'discount_ledger' => 'Rotary Discount Ledger',
                'is_cashless' => true,
                'cashless_applicable_on' => 'opd',
                'pharmacy_cash' => true,
            ],
            [
                'class_code' => 'VIP',
                'class_name' => 'VIP Patient',
                'description' => 'VIP patients requiring special attention',
                'is_cashless' => false,
                'apply_token' => false,
            ],
        ];

        foreach ($classes as $class) {
            PatientClass::create($class);
        }

        // Create Cashless Price Lists for ICICI Lombard Cashless class
        $services = Service::all();
        $iciclClass = PatientClass::where('class_code', 'ICICI-CL')->first();
        $starClass = PatientClass::where('class_code', 'STAR-CL')->first();
        $cghsClass = PatientClass::where('class_code', 'CGHS-CL')->first();

        foreach ($services as $service) {
            // ICICI rates (10% higher than standard)
            CashlessPriceList::create([
                'class_id' => $iciclClass->class_id,
                'service_id' => $service->service_id,
                'opd_rate' => $service->rate * 1.10,
                'ipd_rate' => $service->rate * 1.15,
                'day_emergency_rate' => $service->rate * 1.20,
                'night_emergency_rate' => $service->rate * 1.30,
                'is_approval_required' => $service->rate > 200,
                'effective_from' => now()->startOfYear(),
            ]);

            // Star Health rates (5% lower than standard)
            CashlessPriceList::create([
                'class_id' => $starClass->class_id,
                'service_id' => $service->service_id,
                'opd_rate' => $service->rate * 0.95,
                'ipd_rate' => $service->rate * 1.00,
                'day_emergency_rate' => $service->rate * 1.10,
                'night_emergency_rate' => $service->rate * 1.20,
                'is_approval_required' => $service->rate > 300,
                'effective_from' => now()->startOfYear(),
            ]);

            // CGHS rates (fixed government rates, typically lower)
            CashlessPriceList::create([
                'class_id' => $cghsClass->class_id,
                'service_id' => $service->service_id,
                'opd_rate' => $service->rate * 0.80,
                'ipd_rate' => $service->rate * 0.85,
                'day_emergency_rate' => $service->rate * 0.90,
                'night_emergency_rate' => $service->rate * 1.00,
                'is_approval_required' => false,
                'effective_from' => now()->startOfYear(),
            ]);
        }

        // Create Ward Wise Cost Additions for IPD
        $wards = Ward::all();
        $wwcaServices = Service::whereIn('service_type', ['procedure', 'ipd'])->get();

        foreach ($wwcaServices as $service) {
            foreach ($wards as $ward) {
                // Different rates based on ward type
                $rateMultiplier = match($ward->ward_type) {
                    'icu' => 2.0,
                    'private' => 1.5,
                    'semi_private' => 1.25,
                    default => 1.0,
                };

                WardWiseCostAddition::create([
                    'class_id' => $iciclClass->class_id,
                    'service_id' => $service->service_id,
                    'ward_id' => $ward->ward_id,
                    'rate' => $service->rate * $rateMultiplier,
                    'rate_type' => 'fixed',
                    'effective_from' => now()->startOfYear(),
                ]);
            }
        }

        // Create Payment Modes
        $paymentModes = [
            [
                'mode_code' => 'CASH',
                'mode_name' => 'Cash',
                'description' => 'Cash payment',
                'has_financial_details' => false,
                'use_for_refund' => true,
            ],
            [
                'mode_code' => 'CARD',
                'mode_name' => 'Credit/Debit Card',
                'description' => 'Payment via credit or debit card',
                'has_financial_details' => true,
                'use_for_refund' => true,
                'is_title_mandatory' => true,
                'post_charges_applicable' => true,
                'post_charge_percent' => 2.0,
            ],
            [
                'mode_code' => 'CHEQUE',
                'mode_name' => 'Cheque',
                'description' => 'Payment via cheque',
                'has_financial_details' => true,
                'use_for_refund' => false,
                'is_title_mandatory' => true,
            ],
            [
                'mode_code' => 'UPI',
                'mode_name' => 'UPI',
                'description' => 'UPI payment (Google Pay, PhonePe, Paytm, etc.)',
                'has_financial_details' => true,
                'use_for_refund' => true,
                'is_title_mandatory' => true,
            ],
            [
                'mode_code' => 'NEFT',
                'mode_name' => 'NEFT/RTGS',
                'description' => 'Bank transfer via NEFT or RTGS',
                'has_financial_details' => true,
                'use_for_refund' => true,
                'is_title_mandatory' => true,
            ],
            [
                'mode_code' => 'MULTI',
                'mode_name' => 'Multi-Mode',
                'description' => 'Payment using multiple payment methods',
                'has_financial_details' => true,
                'use_for_refund' => false,
            ],
            [
                'mode_code' => 'CREDIT',
                'mode_name' => 'Credit',
                'description' => 'Credit payment (to be collected later)',
                'has_financial_details' => false,
                'use_for_refund' => false,
            ],
        ];

        foreach ($paymentModes as $mode) {
            $paymentMode = PaymentMode::create($mode);

            // Add details based on payment mode
            if ($mode['mode_code'] === 'CARD') {
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'card_number',
                    'field_label' => 'Card Number (Last 4 digits)',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 4,
                    'display_order' => 1,
                ]);
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'card_holder_name',
                    'field_label' => 'Card Holder Name',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 100,
                    'display_order' => 2,
                ]);
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'transaction_id',
                    'field_label' => 'Transaction ID',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 50,
                    'display_order' => 3,
                ]);
            } elseif ($mode['mode_code'] === 'CHEQUE') {
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'cheque_number',
                    'field_label' => 'Cheque Number',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 20,
                    'display_order' => 1,
                ]);
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'cheque_date',
                    'field_label' => 'Cheque Date',
                    'field_type' => 'date',
                    'is_required' => true,
                    'display_order' => 2,
                ]);
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'bank_name',
                    'field_label' => 'Bank Name',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 100,
                    'display_order' => 3,
                ]);
            } elseif ($mode['mode_code'] === 'UPI') {
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'upi_transaction_id',
                    'field_label' => 'UPI Transaction ID',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 50,
                    'display_order' => 1,
                ]);
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'upi_id',
                    'field_label' => 'UPI ID',
                    'field_type' => 'text',
                    'is_required' => false,
                    'max_length' => 50,
                    'display_order' => 2,
                ]);
            } elseif ($mode['mode_code'] === 'NEFT') {
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'reference_number',
                    'field_label' => 'Reference Number',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 50,
                    'display_order' => 1,
                ]);
                PaymentModeDetail::create([
                    'payment_mode_id' => $paymentMode->payment_mode_id,
                    'field_name' => 'bank_name',
                    'field_label' => 'Bank Name',
                    'field_type' => 'text',
                    'is_required' => true,
                    'max_length' => 100,
                    'display_order' => 2,
                ]);
            }
        }

        // Create Cancel Reasons
        $cancelReasons = [
            [
                'reason_code' => 'PAT_REQ',
                'reason_name' => 'Patient Request',
                'description' => 'Cancelled at patient\'s request',
                'applicable_for' => 'both',
            ],
            [
                'reason_code' => 'DOC_UNAVL',
                'reason_name' => 'Doctor Unavailable',
                'description' => 'Doctor is not available',
                'applicable_for' => 'opd',
            ],
            [
                'reason_code' => 'DUP_ENTRY',
                'reason_name' => 'Duplicate Entry',
                'description' => 'Entry was created by mistake',
                'applicable_for' => 'both',
            ],
            [
                'reason_code' => 'NO_SHOW',
                'reason_name' => 'Patient No Show',
                'description' => 'Patient did not arrive for appointment',
                'is_auto_process' => true,
                'applicable_for' => 'opd',
            ],
            [
                'reason_code' => 'INS_REJ',
                'reason_name' => 'Insurance Rejected',
                'description' => 'Insurance/TPA rejected the claim',
                'applicable_for' => 'both',
            ],
            [
                'reason_code' => 'FIN_ISSUE',
                'reason_name' => 'Financial Issues',
                'description' => 'Patient unable to pay',
                'applicable_for' => 'both',
            ],
            [
                'reason_code' => 'REFERRED',
                'reason_name' => 'Referred to Other Hospital',
                'description' => 'Patient referred to another hospital',
                'applicable_for' => 'both',
            ],
            [
                'reason_code' => 'LAMA',
                'reason_name' => 'Left Against Medical Advice',
                'description' => 'Patient left against medical advice',
                'applicable_for' => 'ipd',
            ],
            [
                'reason_code' => 'ABSCOND',
                'reason_name' => 'Patient Absconded',
                'description' => 'Patient left without informing',
                'applicable_for' => 'ipd',
            ],
            [
                'reason_code' => 'EXPIRED',
                'reason_name' => 'Patient Expired',
                'description' => 'Patient expired during treatment',
                'applicable_for' => 'ipd',
            ],
            [
                'reason_code' => 'WRONG_REG',
                'reason_name' => 'Wrong Registration',
                'description' => 'Registration done incorrectly',
                'applicable_for' => 'opd',
            ],
            [
                'reason_code' => 'SYS_ERROR',
                'reason_name' => 'System Error',
                'description' => 'Cancelled due to system error',
                'is_auto_process' => true,
                'applicable_for' => 'both',
            ],
        ];

        foreach ($cancelReasons as $reason) {
            CancelReason::create($reason);
        }

        // Create Vaccinations (Child Immunization Schedule)
        $vaccinations = [
            // At Birth
            [
                'vaccine_code' => 'BCG',
                'vaccine_name' => 'BCG (Bacillus Calmette-Guerin)',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 0,
                'schedule_type' => 'days',
                'schedule_text' => 'At Birth',
                'dose_number' => 1,
                'total_doses' => 1,
                'description' => 'Vaccine against tuberculosis',
                'instructions' => 'Intradermal injection on left upper arm',
                'rate' => 50,
            ],
            [
                'vaccine_code' => 'OPV0',
                'vaccine_name' => 'OPV (Oral Polio Vaccine) - Birth Dose',
                'manufacturer' => 'Bharat Biotech',
                'schedule_value' => 0,
                'schedule_type' => 'days',
                'schedule_text' => 'At Birth',
                'dose_number' => 1,
                'total_doses' => 5,
                'description' => 'Oral polio vaccine birth dose',
                'instructions' => '2 drops orally',
                'rate' => 30,
            ],
            [
                'vaccine_code' => 'HEPB0',
                'vaccine_name' => 'Hepatitis B - Birth Dose',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 0,
                'schedule_type' => 'days',
                'schedule_text' => 'At Birth',
                'dose_number' => 1,
                'total_doses' => 4,
                'description' => 'Hepatitis B vaccine birth dose',
                'instructions' => 'Intramuscular injection in anterolateral thigh',
                'rate' => 100,
            ],
            // 6 Weeks (42 days)
            [
                'vaccine_code' => 'DTP1',
                'vaccine_name' => 'DTP (Diphtheria, Tetanus, Pertussis) - 1st Dose',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 42,
                'schedule_type' => 'days',
                'schedule_text' => '6 Weeks',
                'dose_number' => 1,
                'total_doses' => 5,
                'description' => 'Combined vaccine for diphtheria, tetanus, and pertussis',
                'instructions' => 'Intramuscular injection',
                'rate' => 150,
            ],
            [
                'vaccine_code' => 'IPV1',
                'vaccine_name' => 'IPV (Inactivated Polio Vaccine) - 1st Dose',
                'manufacturer' => 'Sanofi Pasteur',
                'schedule_value' => 42,
                'schedule_type' => 'days',
                'schedule_text' => '6 Weeks',
                'dose_number' => 1,
                'total_doses' => 3,
                'description' => 'Injectable polio vaccine',
                'instructions' => 'Intramuscular injection',
                'rate' => 200,
            ],
            [
                'vaccine_code' => 'HEPB1',
                'vaccine_name' => 'Hepatitis B - 1st Dose',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 42,
                'schedule_type' => 'days',
                'schedule_text' => '6 Weeks',
                'dose_number' => 2,
                'total_doses' => 4,
                'description' => 'Hepatitis B vaccine 1st dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 100,
            ],
            [
                'vaccine_code' => 'HIB1',
                'vaccine_name' => 'Hib (Haemophilus influenzae type b) - 1st Dose',
                'manufacturer' => 'Bharat Biotech',
                'schedule_value' => 42,
                'schedule_type' => 'days',
                'schedule_text' => '6 Weeks',
                'dose_number' => 1,
                'total_doses' => 4,
                'description' => 'Vaccine against Hib diseases',
                'instructions' => 'Intramuscular injection',
                'rate' => 250,
            ],
            [
                'vaccine_code' => 'ROTA1',
                'vaccine_name' => 'Rotavirus Vaccine - 1st Dose',
                'manufacturer' => 'Bharat Biotech',
                'schedule_value' => 42,
                'schedule_type' => 'days',
                'schedule_text' => '6 Weeks',
                'dose_number' => 1,
                'total_doses' => 3,
                'description' => 'Vaccine against rotavirus diarrhea',
                'instructions' => 'Oral drops',
                'rate' => 300,
            ],
            [
                'vaccine_code' => 'PCV1',
                'vaccine_name' => 'PCV (Pneumococcal Conjugate Vaccine) - 1st Dose',
                'manufacturer' => 'Pfizer',
                'schedule_value' => 42,
                'schedule_type' => 'days',
                'schedule_text' => '6 Weeks',
                'dose_number' => 1,
                'total_doses' => 3,
                'description' => 'Vaccine against pneumococcal diseases',
                'instructions' => 'Intramuscular injection',
                'rate' => 3500,
            ],
            // 10 Weeks (70 days)
            [
                'vaccine_code' => 'DTP2',
                'vaccine_name' => 'DTP - 2nd Dose',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 70,
                'schedule_type' => 'days',
                'schedule_text' => '10 Weeks',
                'dose_number' => 2,
                'total_doses' => 5,
                'description' => 'DTP vaccine 2nd dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 150,
            ],
            [
                'vaccine_code' => 'IPV2',
                'vaccine_name' => 'IPV - 2nd Dose',
                'manufacturer' => 'Sanofi Pasteur',
                'schedule_value' => 70,
                'schedule_type' => 'days',
                'schedule_text' => '10 Weeks',
                'dose_number' => 2,
                'total_doses' => 3,
                'description' => 'IPV vaccine 2nd dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 200,
            ],
            // 14 Weeks (98 days)
            [
                'vaccine_code' => 'DTP3',
                'vaccine_name' => 'DTP - 3rd Dose',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 98,
                'schedule_type' => 'days',
                'schedule_text' => '14 Weeks',
                'dose_number' => 3,
                'total_doses' => 5,
                'description' => 'DTP vaccine 3rd dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 150,
            ],
            [
                'vaccine_code' => 'IPV3',
                'vaccine_name' => 'IPV - 3rd Dose',
                'manufacturer' => 'Sanofi Pasteur',
                'schedule_value' => 98,
                'schedule_type' => 'days',
                'schedule_text' => '14 Weeks',
                'dose_number' => 3,
                'total_doses' => 3,
                'description' => 'IPV vaccine 3rd dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 200,
            ],
            // 9 Months
            [
                'vaccine_code' => 'MMR1',
                'vaccine_name' => 'MMR (Measles, Mumps, Rubella) - 1st Dose',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 9,
                'schedule_type' => 'months',
                'schedule_text' => '9 Months',
                'dose_number' => 1,
                'total_doses' => 2,
                'description' => 'Combined vaccine for measles, mumps, and rubella',
                'instructions' => 'Subcutaneous injection',
                'rate' => 350,
            ],
            [
                'vaccine_code' => 'MEASLES',
                'vaccine_name' => 'Measles Vaccine',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 9,
                'schedule_type' => 'months',
                'schedule_text' => '9 Months',
                'dose_number' => 1,
                'total_doses' => 2,
                'description' => 'Measles vaccine',
                'instructions' => 'Subcutaneous injection',
                'rate' => 100,
            ],
            // 12 Months
            [
                'vaccine_code' => 'HEPA1',
                'vaccine_name' => 'Hepatitis A - 1st Dose',
                'manufacturer' => 'GlaxoSmithKline',
                'schedule_value' => 12,
                'schedule_type' => 'months',
                'schedule_text' => '12 Months',
                'dose_number' => 1,
                'total_doses' => 2,
                'description' => 'Hepatitis A vaccine',
                'instructions' => 'Intramuscular injection',
                'rate' => 1500,
            ],
            // 15 Months
            [
                'vaccine_code' => 'MMR2',
                'vaccine_name' => 'MMR - 2nd Dose',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 15,
                'schedule_type' => 'months',
                'schedule_text' => '15 Months',
                'dose_number' => 2,
                'total_doses' => 2,
                'description' => 'MMR vaccine 2nd dose',
                'instructions' => 'Subcutaneous injection',
                'rate' => 350,
            ],
            [
                'vaccine_code' => 'VARICELLA1',
                'vaccine_name' => 'Varicella (Chickenpox) - 1st Dose',
                'manufacturer' => 'Merck',
                'schedule_value' => 15,
                'schedule_type' => 'months',
                'schedule_text' => '15 Months',
                'dose_number' => 1,
                'total_doses' => 2,
                'description' => 'Chickenpox vaccine',
                'instructions' => 'Subcutaneous injection',
                'rate' => 1800,
            ],
            // 16-18 Months
            [
                'vaccine_code' => 'DTP-B1',
                'vaccine_name' => 'DTP Booster - 1st',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 18,
                'schedule_type' => 'months',
                'schedule_text' => '16-18 Months',
                'dose_number' => 4,
                'total_doses' => 5,
                'description' => 'DTP booster dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 150,
            ],
            [
                'vaccine_code' => 'HIB-B',
                'vaccine_name' => 'Hib Booster',
                'manufacturer' => 'Bharat Biotech',
                'schedule_value' => 18,
                'schedule_type' => 'months',
                'schedule_text' => '16-18 Months',
                'dose_number' => 4,
                'total_doses' => 4,
                'description' => 'Hib booster dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 250,
            ],
            // 4-6 Years
            [
                'vaccine_code' => 'DTP-B2',
                'vaccine_name' => 'DTP Booster - 2nd',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 5,
                'schedule_type' => 'years',
                'schedule_text' => '4-6 Years',
                'dose_number' => 5,
                'total_doses' => 5,
                'description' => 'DTP booster 2nd dose',
                'instructions' => 'Intramuscular injection',
                'rate' => 150,
            ],
            // 10 Years
            [
                'vaccine_code' => 'TDAP',
                'vaccine_name' => 'Tdap (Tetanus, Diphtheria, Pertussis)',
                'manufacturer' => 'Sanofi Pasteur',
                'schedule_value' => 10,
                'schedule_type' => 'years',
                'schedule_text' => '10 Years',
                'dose_number' => 1,
                'total_doses' => 1,
                'description' => 'Tetanus, diphtheria, pertussis booster',
                'instructions' => 'Intramuscular injection',
                'rate' => 1200,
            ],
            [
                'vaccine_code' => 'HPV1',
                'vaccine_name' => 'HPV (Human Papillomavirus) - 1st Dose',
                'manufacturer' => 'Merck',
                'schedule_value' => 10,
                'schedule_type' => 'years',
                'schedule_text' => '10-12 Years (Girls)',
                'dose_number' => 1,
                'total_doses' => 2,
                'description' => 'HPV vaccine for cervical cancer prevention',
                'instructions' => 'Intramuscular injection',
                'rate' => 3500,
            ],
            // Adult Vaccines
            [
                'vaccine_code' => 'FLU',
                'vaccine_name' => 'Influenza (Flu) Vaccine',
                'manufacturer' => 'Sanofi Pasteur',
                'schedule_value' => 0,
                'schedule_type' => 'years',
                'schedule_text' => 'Annually (6 months+)',
                'dose_number' => 1,
                'total_doses' => 1,
                'description' => 'Annual flu vaccine',
                'instructions' => 'Intramuscular injection',
                'rate' => 800,
            ],
            [
                'vaccine_code' => 'COVID19',
                'vaccine_name' => 'COVID-19 Vaccine',
                'manufacturer' => 'Serum Institute of India',
                'schedule_value' => 12,
                'schedule_type' => 'years',
                'schedule_text' => '12 Years+',
                'dose_number' => 1,
                'total_doses' => 2,
                'description' => 'COVID-19 vaccine',
                'instructions' => 'Intramuscular injection',
                'rate' => 0,
            ],
            [
                'vaccine_code' => 'TYPHOID',
                'vaccine_name' => 'Typhoid Vaccine',
                'manufacturer' => 'Bharat Biotech',
                'schedule_value' => 2,
                'schedule_type' => 'years',
                'schedule_text' => '2 Years+',
                'dose_number' => 1,
                'total_doses' => 1,
                'description' => 'Typhoid fever vaccine',
                'instructions' => 'Intramuscular injection, revaccination every 3 years',
                'rate' => 400,
            ],
        ];

        foreach ($vaccinations as $vaccination) {
            Vaccination::create($vaccination);
        }
    }
}
