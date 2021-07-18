<?php

namespace Crater\Http\Controllers\V1\Settings;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\CompanyRequest;
use Crater\Http\Requests\ProfileRequest;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\UserCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    /**
     * Retrieve a list of existing Items.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->has('limit') ? $request->limit : 10;
        
        $companies = Company::with(['settings', 'currencyInfo'])
                ->applyFilters($request->only([
                'search',
                'orderByField',
                'orderBy',
                ]));
        if(auth()->user()->role != 'super admin') {
            $userCompanies = auth()->user()->user_companies->pluck('company_id')->toArray();
            $companies = $companies->whereIn('id', $userCompanies);
        }
        $companies = $companies->latest()
            ->paginateData($limit);

        return response()->json([
            'companies' => $companies,
            'companyTotalCount' => Company::count(),
        ]);
    }

    /**
     * Create Item.
     *
     * @param  Crater\Http\Requests\ItemsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $company = new Company();
        $company->name = $request->name;
        $company->domain = $request->domain;
        $company->email = $request->email;
        $company->address = $request->address;
        $company->currency = $request->currency ? json_decode($request->currency)->id : '';
        $company->unique_hash = str_random(20);
        $company->save();

        $data = json_decode($request->company_logo);

        if ($data) {
            if ($company) {
                $company->clearMediaCollection('logo');

                $company->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('logo');
            }
        }

        $this->setCompanySetting($request, $company->id);
        return response()->json([
            'company' => $company,
        ]);
    }

    /**
     * get an existing Item.
     *
     * @param  Item $item
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Company $company)
    {
        return response()->json([
            'company' => $company,
        ]);
    }

    /**
     * Update an existing Item.
     *
     * @param  Crater\Http\Requests\ItemsRequest $request
     * @param  \Crater\Models\Item $item
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Company $company)
    {
        $company->name = $request->name;
        $company->domain = $request->domain;
        $company->email = $request->email;
        $company->address = $request->address;
        $company->currency = $request->currency ? $request->currency['id'] : '';
        $company->save();

        $data = json_decode($request->company_logo);

        if ($data) {
            if ($company) {
                $company->clearMediaCollection('logo');

                $company->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('logo');
            }
        }

        $this->setCompanySetting($request, $company->id);
        return response()->json([
            'company' => $company,
        ]);
    }

    /**
     * Delete a list of existing Items.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        CompanySetting::where('company_id', $request->ids)->delete();
        
        Company::destroy($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Retrive the Admin account.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser()
    {
        $user = Auth::user();

        $user->load([
            'addresses',
            'addresses.country',
            'company',
            'company.address',
            'company.address.country',
        ]);

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Update the Admin profile.
     * Includes name, email and (or) password
     *
     * @param  \Crater\Http\Requests\ProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(ProfileRequest $request)
    {
        $user = Auth::user();

        $user->update($request->validated());

        return response()->json([
            'user' => $user,
            'success' => true,
        ]);
    }

    /**
     * Update Admin Company Details
     * @param \Crater\Http\Requests\CompanyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCompany(CompanyRequest $request)
    {
        $company = Auth::user()->company;

        $company->update($request->only('name'));

        $company->address()->updateOrCreate(['company_id' => $company->id], $request->except(['name']));

        return response()->json([
            'company' => $company,
            'success' => true,
        ]);
    }

    /**
     * Upload the company logo to storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadCompanyLogo(Request $request)
    {
        $data = json_decode($request->company_logo);

        if ($data) {
            $company = Company::find($request->header('company'));

            if ($company) {
                $company->clearMediaCollection('logo');

                $company->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('logo');
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Upload the company logo to storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCompanyLogo(Request $request)
    {
        $data = json_decode($request->company_logo);

        if ($data) {
            $company = Company::find($request->company_id);

            if ($company) {
                $company->clearMediaCollection('logo');

                $company->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('logo');
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Upload the Admin Avatar to public storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(Request $request)
    {
        $data = json_decode($request->admin_avatar);

        if ($data) {
            $user = auth()->user();

            if ($user) {
                $user->clearMediaCollection('admin_avatar');

                $user->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('admin_avatar');
            }
        }

        return response()->json([
            'user' => $user,
            'success' => true,
        ]);
    }

    public function setCompanySetting($data, $companyId) {
        
        $defaultInvoiceEmailBody = 'You have received a new invoice from <b>{COMPANY_NAME}</b>.</br> Please download using the button below:';
        $defaultEstimateEmailBody = 'You have received a new estimate from <b>{COMPANY_NAME}</b>.</br> Please download using the button below:';
        $defaultPaymentEmailBody = 'Thank you for the payment.</b></br> Please download your payment receipt using the button below:';
        $billingAddressFormat = '<h3>{BILLING_ADDRESS_NAME}</h3><p>{BILLING_ADDRESS_STREET_1}</p><p>{BILLING_ADDRESS_STREET_2}</p><p>{BILLING_CITY}  {BILLING_STATE}</p><p>{BILLING_COUNTRY}  {BILLING_ZIP_CODE}</p><p>{BILLING_PHONE}</p>';
        $shippingAddressFormat = '<h3>{SHIPPING_ADDRESS_NAME}</h3><p>{SHIPPING_ADDRESS_STREET_1}</p><p>{SHIPPING_ADDRESS_STREET_2}</p><p>{SHIPPING_CITY}  {SHIPPING_STATE}</p><p>{SHIPPING_COUNTRY}  {SHIPPING_ZIP_CODE}</p><p>{SHIPPING_PHONE}</p>';
        $companyAddressFormat = '<h3><strong>{COMPANY_NAME}</strong></h3><p>{COMPANY_ADDRESS_STREET_1}</p><p>{COMPANY_ADDRESS_STREET_2}</p><p>{COMPANY_CITY} {COMPANY_STATE}</p><p>{COMPANY_COUNTRY}  {COMPANY_ZIP_CODE}</p><p>{COMPANY_PHONE}</p>';
        $paymentFromCustomerAddress = '<h3>{BILLING_ADDRESS_NAME}</h3><p>{BILLING_ADDRESS_STREET_1}</p><p>{BILLING_ADDRESS_STREET_2}</p><p>{BILLING_CITY} {BILLING_STATE} {BILLING_ZIP_CODE}</p><p>{BILLING_COUNTRY}</p><p>{BILLING_PHONE}</p>';

        $settings = [
            'domain' => $data->domain,
            'email' => $data->email,
            'address' => $data->address,
            'invoice_auto_generate' => 'YES',
            'payment_auto_generate' => 'YES',
            'estimate_auto_generate' => 'YES',
            'save_pdf_to_disk' => 'NO',
            'invoice_mail_body' => $defaultInvoiceEmailBody,
            'estimate_mail_body' => $defaultEstimateEmailBody,
            'payment_mail_body' => $defaultPaymentEmailBody,
            'invoice_company_address_format' => $companyAddressFormat,
            'invoice_shipping_address_format' => $shippingAddressFormat,
            'invoice_billing_address_format' => $billingAddressFormat,
            'estimate_company_address_format' => $companyAddressFormat,
            'estimate_shipping_address_format' => $shippingAddressFormat,
            'estimate_billing_address_format' => $billingAddressFormat,
            'payment_company_address_format' => $companyAddressFormat,
            'payment_from_customer_address_format' => $paymentFromCustomerAddress,
            'currency' => $data->currency,
            'time_zone' => 'Asia/Kolkata',
            'language' => 'en',
            'fiscal_year' => '1-12',
            'carbon_date_format' => 'Y/m/d',
            'moment_date_format' => 'YYYY/MM/DD',
            'notification_email' => 'noreply@'.$data->domain,
            'notify_invoice_viewed' => 'NO',
            'notify_estimate_viewed' => 'NO',
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'invoice_prefix' => 'INV',
            'invoice_auto_generate' => 'YES',
            'invoice_number_length' => 6,
            'invoice_email_attachment' => 'NO',
            'estimate_prefix' => 'EST',
            'estimate_auto_generate' => 'YES',
            'estimate_number_length' => 6,
            'estimate_email_attachment' => 'NO',
            'payment_prefix' => 'PAY',
            'payment_auto_generate' => 'YES',
            'payment_number_length' => 6,
            'payment_email_attachment' => 'NO',
            'save_pdf_to_disk' => 'NO',
        ];

        CompanySetting::setSettings($settings, $companyId);
    }

    public function fetchAll() {
        return response()->json([
            'companies' => Company::get(),
            'companyTotalCount' => Company::count(),
        ]);
    }
}
