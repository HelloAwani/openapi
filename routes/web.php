<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
Route::post('check', 'BaseController@checkCon');
// item category
Route::post('category/datatables', 'ItemCategoryController@datatables');
Route::post('category/detail', 'ItemCategoryController@detail');
Route::post('category/save', 'ItemCategoryController@save');
Route::post('category/delete', 'ItemCategoryController@delete');
Route::post('category/import', 'ItemCategoryController@import');
// item
Route::post('item/datatables', 'ItemController@datatables');
Route::post('item/detail', 'ItemController@detail');
Route::post('item/save', 'ItemController@save');
Route::post('item/delete', 'ItemController@delete');
Route::post('item/import', 'ItemController@import');
// shift
Route::post('shift/datatables', 'ShiftController@datatables');
Route::post('shift/save', 'ShiftController@save');
Route::post('shift/detail', 'ShiftController@detail');
Route::post('shift/delete', 'ShiftController@delete');
// customer
Route::post('customer/datatables', 'CustomerController@datatables');
Route::post('customer/save', 'CustomerController@save');
Route::post('customer/detail', 'CustomerController@detail');
Route::post('customer/delete', 'CustomerController@delete');
// expense
Route::post('expense/datatables', 'ExpenseController@datatables');
Route::post('expense/save', 'ExpenseController@save');
Route::post('expense/detail', 'ExpenseController@detail');
Route::post('expense/delete', 'ExpenseController@delete');
// shift
Route::post('expense_type/datatables', 'ExpenseTypeController@datatables');
Route::post('expense_type/save', 'ExpenseTypeController@save');
Route::post('expense_type/detail', 'ExpenseTypeController@detail');
Route::post('expense_type/delete', 'ExpenseTypeController@delete');
// service
Route::post('service/datatables', 'ServiceController@datatables');
Route::post('service/save', 'ServiceController@save');
Route::post('service/detail', 'ServiceController@detail');
Route::post('service/delete', 'ServiceController@delete');
Route::post('service/import', 'ServiceController@import');
// subservice
Route::post('subservice/datatables', 'SubServiceController@datatables');
Route::post('subservice/save', 'SubServiceController@save');
Route::post('subservice/detail', 'SubServiceController@detail');
Route::post('subservice/delete', 'SubServiceController@delete');
// usertype
Route::post('usertype/datatables', 'UserTypeController@datatables');
Route::post('usertype/save', 'UserTypeController@save');
Route::post('usertype/detail', 'UserTypeController@detail');
Route::post('usertype/delete', 'UserTypeController@delete');
//Route::get('usertype/validate', 'UserTypeController@validateTry');
// paymentmethod
Route::post('paymentmethod/datatables', 'PaymentMethodController@datatables');
Route::post('paymentmethod/save', 'PaymentMethodController@save');
Route::post('paymentmethod/detail', 'PaymentMethodController@detail');
Route::post('paymentmethod/delete', 'PaymentMethodController@delete');
// spacesection
Route::post('spacesection/datatables', 'SpaceSectionController@datatables');
Route::post('spacesection/save', 'SpaceSectionController@save');
Route::post('spacesection/detail', 'SpaceSectionController@detail');
Route::post('spacesection/delete', 'SpaceSectionController@delete');
// space
Route::post('space/datatables', 'SpaceController@datatables');
Route::post('space/save', 'SpaceController@save');
Route::post('space/detail', 'SpaceController@detail');
Route::post('space/delete', 'SpaceController@delete');
// space
Route::post('discount/datatables', 'DiscountController@datatables');
Route::post('discount/save', 'DiscountController@save');
Route::post('discount/detail', 'DiscountController@detail');
Route::post('discount/delete', 'DiscountController@delete');
// staf /users
Route::post('user/datatables', 'StaffController@datatables');
Route::post('user/save', 'StaffController@save');
Route::post('user/detail', 'StaffController@detail');
Route::post('user/delete', 'StaffController@delete');
//item conversion
Route::post('item_conversion/datatables', 'ItemConversionController@datatables');
Route::post('item_conversion/save', 'ItemConversionController@save');
Route::post('item_conversion/detail', 'ItemConversionController@detail');
Route::post('item_conversion/delete', 'ItemConversionController@delete');
//service usage
Route::post('service_usage/datatables', 'ServiceUsageController@datatables');
Route::post('service_usage/save', 'ServiceUsageController@save');
Route::post('service_usage/detail', 'ServiceUsageController@detail');
Route::post('service_usage/delete', 'ServiceUsageController@delete');
// meta
Route::post('meta/get_user_permission_list', 'BaseController@getPermissions');
Route::post('meta/category', 'MetaController@getCategory');
Route::post('meta/shift', 'MetaController@getShift');
Route::post('meta/usertype', 'MetaController@getUserType');
Route::post('meta/unitduration', 'MetaController@getUnitDuration');
Route::post('meta/paymentmethod', 'MetaController@getPaymentMethod');
Route::post('meta/spacesection', 'MetaController@getSpaceSection');
Route::post('meta/unit_type', 'MetaController@getUnitType');
Route::post('meta/item', 'MetaController@getItem');
Route::post('meta/sub_service', 'MetaController@getSubService');
//general
Route::post('general/detail', 'GeneralSettingController@detail');
Route::post('general/save', 'GeneralSettingController@save');
Route::post('upload', 'UploadController@upload');
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
// item category
Route::post('category/datatables', 'ItemCategoryController@datatables');
Route::post('category/detail', 'ItemCategoryController@detail');
Route::post('category/save', 'ItemCategoryController@save');
Route::post('category/delete', 'ItemCategoryController@delete');
// item
Route::post('item/datatables', 'ItemController@datatables');
Route::post('item/detail', 'ItemController@detail');
Route::post('item/save', 'ItemController@save');
Route::post('item/delete', 'ItemController@delete');
// shift
Route::post('shift/datatables', 'ShiftController@datatables');
Route::post('shift/save', 'ShiftController@save');
Route::post('shift/detail', 'ShiftController@detail');
Route::post('shift/delete', 'ShiftController@delete');
// service
Route::post('service/datatables', 'ServiceController@datatables');
Route::post('service/save', 'ServiceController@save');
Route::post('service/detail', 'ServiceController@detail');
Route::post('service/delete', 'ServiceController@delete');
// subservice
Route::post('subservice/datatables', 'SubServiceController@datatables');
Route::post('subservice/save', 'SubServiceController@save');
Route::post('subservice/detail', 'SubServiceController@detail');
Route::post('subservice/delete', 'SubServiceController@delete');
// usertype
Route::post('usertype/datatables', 'UserTypeController@datatables');
Route::post('usertype/save', 'UserTypeController@save');
Route::post('usertype/detail', 'UserTypeController@detail');
Route::post('usertype/delete', 'UserTypeController@delete');
//Route::get('usertype/validate', 'UserTypeController@validateTry');
// paymentmethod
Route::post('paymentmethod/datatables', 'PaymentMethodController@datatables');
Route::post('paymentmethod/save', 'PaymentMethodController@save');
Route::post('paymentmethod/detail', 'PaymentMethodController@detail');
Route::post('paymentmethod/delete', 'PaymentMethodController@delete');
// spacesection
Route::post('spacesection/datatables', 'SpaceSectionController@datatables');
Route::post('spacesection/save', 'SpaceSectionController@save');
Route::post('spacesection/detail', 'SpaceSectionController@detail');
Route::post('spacesection/delete', 'SpaceSectionController@delete');
// space
Route::post('space/datatables', 'SpaceController@datatables');
Route::post('space/save', 'SpaceController@save');
Route::post('space/detail', 'SpaceController@detail');
Route::post('space/delete', 'SpaceController@delete');
// space
Route::post('discount/datatables', 'DiscountController@datatables');
Route::post('discount/save', 'DiscountController@save');
Route::post('discount/detail', 'DiscountController@detail');
Route::post('discount/delete', 'DiscountController@delete');
// staf /users
Route::post('user/datatables', 'StaffController@datatables');
Route::post('user/save', 'StaffController@save');
Route::post('user/detail', 'StaffController@detail');
Route::post('user/delete', 'StaffController@delete');
//item conversion
Route::post('item_conversion/datatables', 'ItemConversionController@datatables');
Route::post('item_conversion/save', 'ItemConversionController@save');
Route::post('item_conversion/detail', 'ItemConversionController@detail');
Route::post('item_conversion/delete', 'ItemConversionController@delete');
//service usage
Route::post('service_usage/datatables', 'ServiceUsageController@datatables');
Route::post('service_usage/save', 'ServiceUsageController@save');
Route::post('service_usage/detail', 'ServiceUsageController@detail');
Route::post('service_usage/delete', 'ServiceUsageController@delete');
//inventory transaction
Route::post('inventory_transaction/datatables', 'InventoryTransactionController@datatables');
Route::post('inventory_transaction/good_receive', 'InventoryTransactionController@good_receive');
Route::post('inventory_transaction/stock_take', 'InventoryTransactionController@stock_take');
Route::post('inventory_transaction/detail', 'InventoryTransactionController@detail');
// meta
Route::post('meta/get_user_permission_list', 'BaseController@getPermissions');
Route::post('meta/category', 'MetaController@getCategory');
Route::post('meta/shift', 'MetaController@getShift');
Route::post('meta/usertype', 'MetaController@getUserType');
Route::post('meta/unitduration', 'MetaController@getUnitDuration');
Route::post('meta/paymentmethod', 'MetaController@getPaymentMethod');
Route::post('meta/spacesection', 'MetaController@getSpaceSection');
Route::post('meta/unit_type', 'MetaController@getUnitType');
Route::post('meta/item', 'MetaController@getItem');
Route::post('meta/expense_type', 'MetaController@getExpenseType');
Route::post('meta/user', 'MetaController@getUser');
Route::post('meta/item_stock', 'MetaController@getItemStock');
Route::post('meta/sub_service', 'MetaController@getSubService');
Route::post('meta/permission_list', 'MetaController@getPermissionList');
Route::post('meta/unit_conversion', 'MetaController@getConversionUnit');
Route::post('meta/commission_formula', 'MetaController@getCommissionFormula');
Route::post('meta/get_detail_branch', 'MetaController@getDetailBranch');

//general
Route::post('general/detail', 'GeneralSettingController@detail');
Route::post('general/save', 'GeneralSettingController@save');
Route::post('upload', 'UploadController@upload');



// report
Route::post('report/service_commision', 'ReportController@serviceCommission');
Route::post('report/item_commision', 'ReportController@itemCommission');
Route::post('report/sales_time_period', 'ReportController@salesTimePeriod');
Route::post('report/dashboard', 'ReportController@mergeDashboard');
Route::post('report/profit_loss', 'ReportController@profitLoss');
Route::post('report/daily_sales', 'ReportController@dailySales');
Route::post('report/commission_report', 'ReportController@commission');
Route::post('report/commission_breakdown_service', 'ReportController@commissionBreakdownService');
Route::post('report/commission_breakdown_item', 'ReportController@commissionBreakdownItem');
Route::post('report/commission_breakdown_staff', 'ReportController@commissionBreakdownStaff');
Route::post('report/commission_breakdown_history', 'ReportController@commissionBreakdownHistory');

//transaction
Route::post('transaction/header', 'TransactionController@getHeader');
Route::post('transaction/detail', 'TransactionController@getDetail');
Route::post('transaction/item', 'TransactionController@getDetailItem');
Route::post('transaction/service', 'TransactionController@getDetailService');
Route::post('transaction/payment', 'TransactionController@getDetailPayment');

//service user mapping
Route::post('service_user_mapping/datatables', 'ServiceUSerMappingController@datatables');
Route::post('service_user_mapping/save', 'ServiceUSerMappingController@save');

// user login and report
Route::post('login', 'LoginController@login');
Route::post('report/user_service_commission', 'LoginController@userServiceSalesCommission');
Route::post('report/user_item_commision', 'LoginController@userItemSalesCommission');
Route::post('report/user_service_commission_detail', 'LoginController@userServiceSalesCommissionDetail');
Route::post('report/user_item_commision_detail', 'LoginController@userItemSalesCommissionDetail');



// report
Route::post('report/service_commision', 'ReportController@serviceCommission');
Route::post('report/item_commision', 'ReportController@itemCommission');
Route::post('report/sales_time_period', 'ReportController@salesTimePeriod');
Route::post('report/dashboard', 'ReportController@mergeDashboard');
Route::post('report/profit_loss', 'ReportController@profitLoss');
Route::post('report/daily_sales', 'ReportController@dailySales');
Route::post('report/commission_report', 'ReportController@commission');
Route::post('report/commission_breakdown_service', 'ReportController@commissionBreakdownService');
Route::post('report/commission_breakdown_item', 'ReportController@commissionBreakdownItem');
Route::post('report/commission_breakdown_staff', 'ReportController@commissionBreakdownStaff');
Route::post('report/commission_breakdown_history', 'ReportController@commissionBreakdownHistory');
Route::post('report/void_report', 'ReportController@VoidReport');
Route::post('report/void_report_breakdown', 'ReportController@VoidReportBreakdown');
Route::post('report/sales_figures', 'ReportController@matrix');
Route::post('report/stock_balance', 'ReportController@stockBalance');

//transaction
Route::post('transaction/header', 'TransactionController@getHeader');
Route::post('transaction/detail', 'TransactionController@getDetail');
Route::post('transaction/item', 'TransactionController@getDetailItem');
Route::post('transaction/service', 'TransactionController@getDetailService');
Route::post('transaction/payment', 'TransactionController@getDetailPayment');
Route::post('transaction/void_bill', 'TransactionController@VoidBill');
Route::post('transaction/void_service', 'TransactionController@VoidService');
Route::post('transaction/void_item', 'TransactionController@VoidItem');
Route::post('transaction/void_sub_service', 'TransactionController@VoidSubService');

//service user mapping
Route::post('service_user_mapping/datatables', 'ServiceUserMappingController@datatables');
Route::post('service_user_mapping/save', 'ServiceUserMappingController@save');
Route::post('service_user_mapping/detail', 'ServiceUserMappingController@detail');
Route::post('service_user_mapping/save_sub', 'ServiceUserMappingController@save_sub');
Route::post('service_user_mapping/detail_sub', 'ServiceUserMappingController@detail_sub');

// user login and report
Route::post('login', 'LoginController@login');
Route::post('report/user_service_commission', 'LoginController@userServiceSalesCommission');
Route::post('report/user_item_commission', 'LoginController@userItemSalesCommission');
Route::post('report/user_service_commission_detail', 'LoginController@userServiceSalesCommissionDetail');
Route::post('report/user_item_commission_detail', 'LoginController@userItemSalesCommissionDetail');



