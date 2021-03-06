<?php
// Integration tests should not need to use the namespace

$root = realpath(dirname(__FILE__));
require_once $root . '/../../../../src/Includes.php';
require_once $root . '/../../../TestUtil.php';

/**
 * @author Anneli Halld'n, Daniel Brolund, Kristian Grossman-Madsen for Svea Webpay
 */
class InvoicePaymentIntegrationTest extends PHPUnit_Framework_TestCase {

    public function testInvoiceRequestAccepted() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(WebPayItem::individualCustomer()
                        ->setNationalIdNumber(4605092222)
                    )
                    ->setCountryCode("SE")
                    ->setCustomerReference("33")
                    ->setOrderDate("2012-12-12")
                    ->setCurrency("SEK")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);
    }


    public function testInvoiceRequestNLAcceptedWithDoubleHousenumber() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(WebPayItem::individualCustomer()
                        ->setBirthDate(1955, 03, 07)
                        ->setName("Sneider", "Boasman")
                        ->setStreetAddress("Gate 42", "23")     // result of splitStreetAddress w/Svea testperson
                        ->setCoAddress(138)
                        ->setLocality("BARENDRECHT")
                        ->setZipCode("1102 HG")
                        ->setInitials("SB")
                    )
                    ->setCountryCode("NL")
                    ->setCustomerReference("33")
                    ->setOrderDate("2012-12-12")
                    ->setCurrency("SEK")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);
    }


    public function testInvoiceRequestUsingISO8601dateAccepted() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                ->addOrderRow(TestUtil::createOrderRow())
                ->addCustomerDetails(WebPayItem::individualCustomer()->setNationalIdNumber(4605092222))
                ->setCountryCode("SE")
                ->setCustomerReference("33")
                ->setOrderDate( date('c') )
                ->setCurrency("SEK")
                ->useInvoicePayment()
                ->doRequest();

        $this->assertEquals(1, $request->accepted);
    }


    public function testInvoiceRequestDenied() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                ->addOrderRow(TestUtil::createOrderRow())
                ->addCustomerDetails(WebPayItem::individualCustomer()->setNationalIdNumber(4606082222))
                ->setCountryCode("SE")
                ->setCustomerReference("33")
                ->setOrderDate("2012-12-12")
                ->setCurrency("SEK")
                ->useInvoicePayment()
                ->doRequest();

        $this->assertEquals(0, $request->accepted);
    }

    //Turned off?
    public function testInvoiceIndividualForDk() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                ->addOrderRow(TestUtil::createOrderRow())
                ->addCustomerDetails(WebPayItem::individualCustomer()->setNationalIdNumber(2603692503))
                ->setCountryCode("DK")
                ->setCustomerReference("33")
                ->setOrderDate("2012-12-12")
                ->setCurrency("DKK")
                ->useInvoicePayment()
                ->doRequest();

        $this->assertEquals(1, $request->accepted);
    }

    public function testInvoiceCompanySE() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                ->addOrderRow(TestUtil::createOrderRow())
                ->addCustomerDetails(WebPayItem::companyCustomer()->setNationalIdNumber(4608142222))
                ->setCountryCode("SE")
                ->setOrderDate("2012-12-12")
                ->setCurrency("SEK")
                ->useInvoicePayment()
                ->doRequest();

        $this->assertEquals(true, $request->accepted);
    }

    public function testAcceptsFractionalQuantities() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                ->addOrderRow( WebPayItem::orderRow()
                    ->setAmountExVat(80.00)
                    ->setVatPercent(25)
                    ->setQuantity(1.25)
                )
                ->addCustomerDetails( TestUtil::createIndividualCustomer("SE") )
                ->setCountryCode("SE")
                ->setCustomerReference("33")
                ->setOrderDate("2012-12-12")
                ->setCurrency("EUR")
                ->useInvoicePayment()
                ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertEquals(0, $request->resultcode);
        $this->assertEquals('Invoice', $request->orderType);
        $this->assertEquals(1, $request->sveaWillBuyOrder);
        $this->assertEquals(125, $request->amount);
    }

    public function testAcceptsIntegerQuantities() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                ->addOrderRow( WebPayItem::orderRow()
                    ->setAmountExVat(80.00)
                    ->setVatPercent(25)
                    ->setQuantity(1)
                )
                ->addCustomerDetails( TestUtil::createIndividualCustomer("SE") )
                ->setCountryCode("SE")
                ->setCustomerReference("33")
                ->setOrderDate("2012-12-12")
                ->setCurrency("EUR")
                ->useInvoicePayment()
                ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertEquals(0, $request->resultcode);
        $this->assertEquals('Invoice', $request->orderType);
        $this->assertEquals(1, $request->sveaWillBuyOrder);
        $this->assertEquals(100, $request->amount);
    }

    /**
     * NL vat rates are 6%, 21% (as of 131018, see http://www.government.nl/issues/taxation/vat-and-excise-duty)
     */
    public function t___estNLInvoicePaymentAcceptsVatRates() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                ->addOrderRow( TestUtil::createOrderRowWithVat( 6 ) )
                ->addOrderRow( TestUtil::createOrderRowWithVat( 21 ) )
                ->addCustomerDetails( TestUtil::createIndividualCustomer("NL") )
                ->setCountryCode("NL")
                ->setCustomerReference("33")
                ->setOrderDate("2012-12-12")
                ->setCurrency("EUR")
                ->useInvoicePayment()
                ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertEquals(0, $request->resultcode);
        $this->assertEquals('Invoice', $request->orderType);
        $this->assertEquals(1, $request->sveaWillBuyOrder);
        $this->assertEquals(106 + 121, $request->amount);           // 1x100 @ 6% vat + 1x100 @ 21%
        $this->assertEquals('', $request->customerIdentity->email);
        $this->assertEquals('', $request->customerIdentity->ipAddress);
        $this->assertEquals('NL', $request->customerIdentity->countryCode);
        $this->assertEquals(23, $request->customerIdentity->houseNumber);
        $this->assertEquals('Individual', $request->customerIdentity->customerType);
        $this->assertEquals('', $request->customerIdentity->phoneNumber);
        $this->assertEquals('Sneider Boasman', $request->customerIdentity->fullName);
        $this->assertEquals('Gate 42', $request->customerIdentity->street);
        $this->assertEquals(138, $request->customerIdentity->coAddress);
        $this->assertEquals('1102 HG', $request->customerIdentity->zipCode);
        $this->assertEquals('BARENDRECHT', $request->customerIdentity->locality);
    }

    /**
     * make sure opencart bug w/corporate invoice payments for one 25% vat product with free shipping (0% vat)
     * resulting in request with illegal vat rows of 24% not originating in integration package
     */

    public function test_InvoiceFee_ExVatAndVatPercent() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $order = WebPay::createOrder($config);
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(2032.80)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addOrderRow(WebPayItem::shippingFee()
                ->setAmountExVat(0.00)
                ->setVatPercent(0)
                )
                ->addOrderRow(WebPayItem::invoiceFee()
                ->setAmountExVat(29.00)
                ->setVatPercent(25)
                )

                ->addCustomerDetails( TestUtil::createCompanyCustomer("SE") )
                ->setCountryCode("SE")
                ->setOrderDate("2013-10-28")
                ->setCurrency("SEK");

        // asserts on request
        $request = $order->useInvoicePayment()->prepareRequest();

        $newRows = $request->request->CreateOrderInformation->OrderRows['OrderRow'];

        $newRow = $newRows[0];
        $this->assertEquals(2032.80, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        $newRow = $newRows[1];
        $this->assertEquals(0, $newRow->PricePerUnit);
        $this->assertEquals(0, $newRow->VatPercent);

        $newRow = $newRows[2];
        $this->assertEquals(29.00, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        // asserts on result
        $result = $order->useInvoicePayment()->doRequest();

        $this->assertEquals(1, $result->accepted);
        $this->assertEquals(0, $result->resultcode);
        $this->assertEquals('Invoice', $result->orderType);
        $this->assertEquals(1, $result->sveaWillBuyOrder);
        $this->assertEquals(2577.25, $result->amount);

    }

    public function test_InvoiceFee_IncVatAndVatPercent() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $order = WebPay::createOrder($config);
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addOrderRow(WebPayItem::shippingFee()
                ->setAmountIncVat(0.00)
                ->setVatPercent(0)
                )
                ->addOrderRow(WebPayItem::invoiceFee()
                ->setAmountIncVat(29.00)
                ->setVatPercent(25)
                )

                ->addCustomerDetails( TestUtil::createCompanyCustomer("SE") )
                ->setCountryCode("SE")
                ->setOrderDate("2013-10-28")
                ->setCurrency("SEK");

        // asserts on request
        $request = $order->useInvoicePayment()->prepareRequest();

        $newRows = $request->request->CreateOrderInformation->OrderRows['OrderRow'];

        $newRow = $newRows[0];
        $this->assertEquals(100, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        $newRow = $newRows[1];
        $this->assertEquals(0, $newRow->PricePerUnit);
        $this->assertEquals(0, $newRow->VatPercent);

        $newRow = $newRows[2];
        $this->assertEquals(23.20, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        // asserts on result
        $result = $order->useInvoicePayment()->doRequest();

        $this->assertEquals(1, $result->accepted);
        $this->assertEquals(0, $result->resultcode);
        $this->assertEquals('Invoice', $result->orderType);
        $this->assertEquals(1, $result->sveaWillBuyOrder);
        $this->assertEquals(154, $result->amount);
    }

    public function test_InvoiceFee_IncVatAndExVat() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $order = WebPay::createOrder($config);
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addOrderRow(WebPayItem::shippingFee()
                ->setAmountIncVat(0.00)
                ->setVatPercent(0)
                )
                ->addOrderRow(WebPayItem::invoiceFee()
                ->setAmountIncVat(29.00)
                ->setAmountExVat(23.20)
                )
                ->addCustomerDetails( TestUtil::createCompanyCustomer("SE") )
                ->setCountryCode("SE")
                ->setOrderDate("2013-10-28")
                ->setCurrency("SEK");

        // asserts on request
        $request = $order->useInvoicePayment()->prepareRequest();
        $newRows = $request->request->CreateOrderInformation->OrderRows['OrderRow'];

        $newRow = $newRows[0];
        $this->assertEquals(100, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        $newRow = $newRows[1];
        $this->assertEquals(0, $newRow->PricePerUnit);
        $this->assertEquals(0, $newRow->VatPercent);

        $newRow = $newRows[2];
        $this->assertEquals(23.20, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        // asserts on result
        $result = $order->useInvoicePayment()->doRequest();

        $this->assertEquals(1, $result->accepted);
        $this->assertEquals(0, $result->resultcode);
        $this->assertEquals('Invoice', $result->orderType);
        $this->assertEquals(1, $result->sveaWillBuyOrder);
        $this->assertEquals(154, $result->amount);
    }

    public function test_ShippingFee_ExVatAndVatPercent() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $order = WebPay::createOrder($config);
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addOrderRow(WebPayItem::shippingFee()
                ->setAmountExVat(20.00)
                ->setVatPercent(6)
                )
                ->addOrderRow(WebPayItem::invoiceFee()
                ->setAmountExVat(23.20)
                ->setVatPercent(25)
                )

                ->addCustomerDetails( TestUtil::createCompanyCustomer("SE") )
                ->setCountryCode("SE")
                ->setOrderDate("2013-10-28")
                ->setCurrency("SEK");

        // asserts on request
        $request = $order->useInvoicePayment()->prepareRequest();

        $newRows = $request->request->CreateOrderInformation->OrderRows['OrderRow'];

        $newRow = $newRows[0];
        $this->assertEquals(100, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        $newRow = $newRows[1];
        $this->assertEquals(20.00, $newRow->PricePerUnit);
        $this->assertEquals(6, $newRow->VatPercent);

        $newRow = $newRows[2];
        $this->assertEquals(23.20, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        // asserts on result
        $result = $order->useInvoicePayment()->doRequest();

        $this->assertEquals(1, $result->accepted);
        $this->assertEquals(0, $result->resultcode);
        $this->assertEquals('Invoice', $result->orderType);
        $this->assertEquals(1, $result->sveaWillBuyOrder);
        $this->assertEquals(175.2, $result->amount);
    }

    public function test_ShippingFee_IncVatAndVatPercent() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $order = WebPay::createOrder($config);
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addOrderRow(WebPayItem::shippingFee()
                ->setAmountIncVat(21.20)
                ->setVatPercent(6)
                )
                ->addOrderRow(WebPayItem::invoiceFee()
                ->setAmountExVat(23.20)
                ->setVatPercent(25)
                )

                ->addCustomerDetails( TestUtil::createCompanyCustomer("SE") )
                ->setCountryCode("SE")
                ->setOrderDate("2013-10-28")
                ->setCurrency("SEK");

        // asserts on request
        $request = $order->useInvoicePayment()->prepareRequest();

        $newRows = $request->request->CreateOrderInformation->OrderRows['OrderRow'];

        $newRow = $newRows[0];
        $this->assertEquals(100, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        $newRow = $newRows[1];
        $this->assertEquals(20.00, $newRow->PricePerUnit);
        $this->assertEquals(6, $newRow->VatPercent);

        $newRow = $newRows[2];
        $this->assertEquals(23.20, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        // asserts on result
        $result = $order->useInvoicePayment()->doRequest();

        $this->assertEquals(1, $result->accepted);
        $this->assertEquals(0, $result->resultcode);
        $this->assertEquals('Invoice', $result->orderType);
        $this->assertEquals(1, $result->sveaWillBuyOrder);
        $this->assertEquals(175.2, $result->amount);
    }

    public function test_ShippingFee_IncVatAndExVat() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $order = WebPay::createOrder($config);
        $order->addOrderRow(WebPayItem::orderRow()
                ->setAmountExVat(100.00)
                ->setVatPercent(25)
                ->setQuantity(1)
                )
                ->addOrderRow(WebPayItem::shippingFee()
                ->setAmountExVat(20.00)
                ->setAmountIncVat(21.20)
                )
                ->addOrderRow(WebPayItem::invoiceFee()
                ->setAmountExVat(23.20)
                ->setVatPercent(25)
                )

                ->addCustomerDetails( TestUtil::createCompanyCustomer("SE") )
                ->setCountryCode("SE")
                ->setOrderDate("2013-10-28")
                ->setCurrency("SEK");

        // asserts on request
        $request = $order->useInvoicePayment()->prepareRequest();

        $newRows = $request->request->CreateOrderInformation->OrderRows['OrderRow'];

        $newRow = $newRows[0];
        $this->assertEquals(100, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        $newRow = $newRows[1];
        $this->assertEquals(20.00, $newRow->PricePerUnit);
        $this->assertEquals(6, $newRow->VatPercent);

        $newRow = $newRows[2];
        $this->assertEquals(23.20, $newRow->PricePerUnit);
        $this->assertEquals(25, $newRow->VatPercent);

        // asserts on result
        $result = $order->useInvoicePayment()->doRequest();

        $this->assertEquals(1, $result->accepted);
        $this->assertEquals(0, $result->resultcode);
        $this->assertEquals('Invoice', $result->orderType);
        $this->assertEquals(1, $result->sveaWillBuyOrder);
        $this->assertEquals(175.2, $result->amount);
    }

    public function testInvoiceRequest_optional_clientOrderNumber_present_in_response_if_sent() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(WebPayItem::individualCustomer()
                        ->setNationalIdNumber(4605092222)
                    )
                    ->setCountryCode("SE")
                    ->setCustomerReference("33")
                    ->setOrderDate("2012-12-12")
                    ->setCurrency("SEK")
                    ->setClientOrderNumber("I_exist!")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertEquals(true, isset($request->clientOrderNumber) );
        $this->assertEquals("I_exist!", $request->clientOrderNumber);
    }

    public function testInvoiceRequest_optional_clientOrderNumber_not_present_in_response_if_not_sent() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(WebPayItem::individualCustomer()
                        ->setNationalIdNumber(4605092222)
                    )
                    ->setCountryCode("SE")
                    ->setCustomerReference("33")
                    ->setOrderDate("2012-12-12")
                    ->setCurrency("SEK")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertEquals(false, isset($request->clientOrderNumber) );
    }

    public function testInvoiceRequest_OrderType_set_in_response_if_useInvoicePayment_set() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertEquals("Invoice", $request->orderType);
    }

    /**
     * Tests for rounding**
     */

    public function testPriceSetAsExVatAndVatPercent(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountExVat(80.00)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();
        $this->assertEquals(1, $request->accepted);

    }

    public function testFixedDiscountSetAsExVat(){
        $config = Svea\SveaConfig::getDefaultConfig();
              $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountExVat(80.00)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::fixedDiscount()
                            ->setAmountExVat(8)
                            ->setVatPercent(0))
                     ->addFee(WebPayItem::shippingFee()
                                ->setAmountExVat(80.00)
                                ->setVatPercent(24)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();
        $this->assertEquals(1, $request->accepted);

    }

    public function testResponseOrderRowPriceSetAsInkVatAndVatPercentSetAmountAsIncVat(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();
          $this->assertEquals(1, $request->accepted);

    }

    public function testResponseFeeSetAsIncVatAndVatPercentWhenPriceSetAsIncVatAndVatPercent(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addFee(WebPayItem::shippingFee()
                                ->setAmountIncVat(100.00)
                                ->setVatPercent(24)
                            )
                    ->addFee(WebPayItem::invoiceFee()
                                ->setAmountIncVat(100.00)
                                ->setVatPercent(24)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);

    }

    public function testResponseDiscountSetAsIncVatWhenPriceSetAsIncVatAndVatPercent(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::fixedDiscount()->setAmountIncVat(10))
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();
         $this->assertEquals(1, $request->accepted);


    }

    public function testResponseDiscountSetAsExVatAndVatPercentWhenPriceSetAsIncVatAndVatPercent(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::fixedDiscount()
                            ->setAmountIncVat(10)
                            ->setVatPercent(0))
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

         $this->assertEquals(1, $request->accepted);

    }


    public function testResponseDiscountPercentAndVatPercentWhenPriceSetAsIncVatAndVatPercent(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::relativeDiscount()
                                    ->setDiscountPercent(10)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

         $this->assertEquals(1, $request->accepted);

    }

    public function testResponseOrderSetAsIncVatAndExVatAndRelativeDiscount(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setAmountExVat(99.99)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::relativeDiscount()
                            ->setDiscountPercent(10)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);

    }

    public function testResponseOrderAndFixedDiscountSetWithMixedVat(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::fixedDiscount()
                            ->setAmountExVat(9.999)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);

    }
    public function testResponseOrderAndFixedDiscountSetWithMixedVat2(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountExVat(99.99)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::fixedDiscount()
                            ->setAmountIncVat(12.39876)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

         $this->assertEquals(1, $request->accepted);

    }
    public function testResponseOrderAndFixedDiscountSetWithMixedVat3(){
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(123.9876)
                                ->setAmountExVat(99.99)
                                ->setQuantity(1)
                            )
                    ->addDiscount(WebPayItem::fixedDiscount()
                            ->setAmountExVat(9.999)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                    ->setCountryCode("SE")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);

    }

    public function testTaloonRoundingExVat(){
         $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountExVat(116.94)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountExVat(7.26)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountExVat(4.03)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("FI"))
                    ->setCountryCode("FI")
                ->setCurrency("EUR")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();
          $this->assertEquals(1, $request->accepted);
          $this->assertEquals(159.01, $request->amount);//sends the old way, so still wrong rounding

    }
    public function testTaloonRoundingIncVat(){
         $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(145.00)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(9.00)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addOrderRow(
                            WebPayItem::orderRow()
                                ->setAmountIncVat(5.00)
                                ->setVatPercent(24)
                                ->setQuantity(1)
                            )
                    ->addCustomerDetails(TestUtil::createIndividualCustomer("FI"))
                    ->setCountryCode("FI")
                ->setCurrency("EUR")
                    ->setOrderDate("2012-12-12")
                    ->useInvoicePayment()
                        ->doRequest();
          $this->assertEquals(1, $request->accepted);
        $this->assertEquals(159.0, $request->amount);

    }

    // Test that test suite returns complete address in each country
    // SE
    // IndividualCustomer validation
    function test_validates_all_required_methods_for_createOrder_useInvoicePayment_IndividualCustomer_SE() {
        $order = WebPay::createOrder(Svea\SveaConfig::getDefaultConfig())
                    ->addOrderRow(
                        WebPayItem::orderRow()
                            ->setQuantity(1.0)
                            ->setAmountExVat(4.0)
                            ->setAmountIncVat(5.0)
                    )
                    ->addCustomerDetails(
                        WebPayItem::individualCustomer()
                            ->setNationalIdNumber("4605092222")
                    )
                    ->setCountryCode("SE")
                    ->setOrderDate(date('c'))
        ;
        $response = $order->useInvoicePayment()->doRequest();
        //print_r($response);
        $this->assertEquals(1, $response->accepted);
        $this->assertTrue( $response->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Persson, Tess T", $response->customerIdentity->fullName );    // Note: order may vary between countries, given by UC
        $this->assertEquals( "Testgatan 1", $response->customerIdentity->street );
        $this->assertEquals( "c/o Eriksson, Erik", $response->customerIdentity->coAddress );
        $this->assertEquals( "99999", $response->customerIdentity->zipCode );
        $this->assertEquals( "Stan", $response->customerIdentity->locality );
    }

    // NO
    // IndividualCustomer validation
    function test_validates_all_required_methods_for_createOrder_useInvoicePayment_IndividualCustomer_NO() {
        $order = WebPay::createOrder(Svea\SveaConfig::getDefaultConfig())
                    ->addOrderRow(
                        WebPayItem::orderRow()
                            ->setQuantity(1.0)
                            ->setAmountExVat(4.0)
                            ->setAmountIncVat(5.0)
                    )
                    ->addCustomerDetails(
                        WebPayItem::individualCustomer()
                            ->setNationalIdNumber("17054512066")
                    )
                    ->setCountryCode("NO")
                    ->setOrderDate(date('c'))
        ;
        $response = $order->useInvoicePayment()->doRequest();
        //print_r($response);
        $this->assertEquals(1, $response->accepted);
        $this->assertTrue( $response->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Normann Ola", $response->customerIdentity->fullName );    // Note: order may vary between countries, given by UC
        $this->assertEquals( "Testveien 2", $response->customerIdentity->street );
        $this->assertEquals( "", $response->customerIdentity->coAddress );
        $this->assertEquals( "359", $response->customerIdentity->zipCode );
        $this->assertEquals( "Oslo", $response->customerIdentity->locality );
    }

    // DK
    // IndividualCustomer validation
    function test_validates_all_required_methods_for_createOrder_useInvoicePayment_IndividualCustomer_DK() {
        $order = WebPay::createOrder(Svea\SveaConfig::getDefaultConfig())
                    ->addOrderRow(
                        WebPayItem::orderRow()
                            ->setQuantity(1.0)
                            ->setAmountExVat(4.0)
                            ->setAmountIncVat(5.0)
                    )
                    ->addCustomerDetails(
                        WebPayItem::individualCustomer()
                            ->setNationalIdNumber("2603692503")
                    )
                    ->setCountryCode("DK")
                    ->setOrderDate(date('c'))
        ;
        $response = $order->useInvoicePayment()->doRequest();
        //print_r($response);
        $this->assertEquals(1, $response->accepted);
        $this->assertTrue( $response->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Jensen Hanne", $response->customerIdentity->fullName );    // Note: order may vary between countries, given by UC
        $this->assertEquals( "Testvejen 42", $response->customerIdentity->street );
        $this->assertEquals( "c/o Test A/S", $response->customerIdentity->coAddress );
        $this->assertEquals( "2100", $response->customerIdentity->zipCode );
        $this->assertEquals( "KØBENHVN Ø", $response->customerIdentity->locality );
    }

    // FI
    // IndividualCustomer validation
    function test_validates_all_required_methods_for_createOrder_useInvoicePayment_IndividualCustomer_FI() {
        $order = WebPay::createOrder(Svea\SveaConfig::getDefaultConfig())
                    ->addOrderRow(
                        WebPayItem::orderRow()
                            ->setQuantity(1.0)
                            ->setAmountExVat(4.0)
                            ->setAmountIncVat(5.0)
                    )
                    ->addCustomerDetails(
                        WebPayItem::individualCustomer()
                            ->setNationalIdNumber("160264-999N")
                    )
                    ->setCountryCode("FI")
                    ->setOrderDate(date('c'))
        ;
        $response = $order->useInvoicePayment()->doRequest();
        //print_r($response);
        $this->assertEquals(1, $response->accepted);
        $this->assertTrue( $response->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Kanerva Haapakoski, Kukka-Maaria", $response->customerIdentity->fullName );    // Note: order may vary between countries, given by UC
        $this->assertEquals( "Atomitie 2 C", $response->customerIdentity->street );
        $this->assertEquals( "", $response->customerIdentity->coAddress );
        $this->assertEquals( "00370", $response->customerIdentity->zipCode );
        $this->assertEquals( "Helsinki", $response->customerIdentity->locality );
    }

    // DE
    // IndividualCustomer validation
    function test_validates_all_required_methods_for_createOrder_useInvoicePayment_IndividualCustomer_DE() {
        $order = WebPay::createOrder(Svea\SveaConfig::getDefaultConfig())
                    ->addOrderRow(
                        WebPayItem::orderRow()
                            ->setQuantity(1.0)
                            ->setAmountExVat(4.0)
                            ->setAmountIncVat(5.0)
                    )
                    ->addCustomerDetails(
                        WebPayItem::individualCustomer()
                            ->setBirthDate("19680403")
                            ->setName("Theo", "Giebel")
                            ->setStreetAddress("Zörgiebelweg", 21)
                            ->setZipCode("13591")
                            ->setLocality("BERLIN")
                    )
                    ->setCountryCode("DE")
                    ->setOrderDate(date('c'))
        ;
        $response = $order->useInvoicePayment()->doRequest();
        //print_r($response);
        $this->assertEquals(1, $response->accepted);
        $this->assertTrue( $response->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Theo Giebel", $response->customerIdentity->fullName );    // Note: order may vary between countries, given by UC
        $this->assertEquals( "Zörgiebelweg", $response->customerIdentity->street );
        $this->assertEquals( "21", $response->customerIdentity->houseNumber );
        $this->assertEquals( "", $response->customerIdentity->coAddress );
        $this->assertEquals( "13591", $response->customerIdentity->zipCode );
        $this->assertEquals( "BERLIN", $response->customerIdentity->locality );
    }

    // NL
    // IndividualCustomer validation
    function test_validates_all_required_methods_for_createOrder_useInvoicePayment_IndividualCustomer_NL() {
        $order = WebPay::createOrder(Svea\SveaConfig::getDefaultConfig())
                    ->addOrderRow(
                        WebPayItem::orderRow()
                            ->setQuantity(1.0)
                            ->setAmountExVat(4.0)
                            ->setAmountIncVat(5.0)
                    )
                    ->addCustomerDetails(
                        WebPayItem::individualCustomer()
                            ->setBirthDate("19550307")
                            ->setInitials("SB")
                            ->setName("Sneider", "Boasman")
                            ->setStreetAddress("Gate 42", 23)
                            ->setZipCode("1102 HG")
                            ->setLocality("BARENDRECHT")
                    )
                    ->setCountryCode("NL")
                    ->setOrderDate(date('c'))
        ;
        $response = $order->useInvoicePayment()->doRequest();
        //print_r($response);
        $this->assertEquals(1, $response->accepted);
        $this->assertTrue( $response->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Sneider Boasman", $response->customerIdentity->fullName );    // Note: order may vary between countries, given by UC
        $this->assertEquals( "Gate 42", $response->customerIdentity->street );
        $this->assertEquals( "23", $response->customerIdentity->houseNumber );
        $this->assertEquals( "", $response->customerIdentity->coAddress );
        $this->assertEquals( "1102 HG", $response->customerIdentity->zipCode );
        $this->assertEquals( "BARENDRECHT", $response->customerIdentity->locality );
    }

    public function testInvoiceRequestNLReturnsSameAddress() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(WebPayItem::individualCustomer()
                        ->setBirthDate(1955, 03, 07)
                        ->setName("Sneider", "Boasman")
                        ->setStreetAddress("Gate 42", "23")     // result of splitStreetAddress w/Svea testperson
                        ->setCoAddress(138)
                        ->setLocality("BARENDRECHT")
                        ->setZipCode("1102 HG")
                        ->setInitials("SB")
                    )
                    ->setCountryCode("NL")
                    ->setCustomerReference("33")
                    ->setOrderDate("2012-12-12")
                    ->setCurrency("SEK")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertTrue( $request->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Sneider Boasman", $request->customerIdentity->fullName );
        $this->assertEquals( "Gate 42", $request->customerIdentity->street );
        $this->assertEquals( "23", $request->customerIdentity->houseNumber );
        $this->assertEquals( "1102 HG", $request->customerIdentity->zipCode );
        $this->assertEquals( "BARENDRECHT", $request->customerIdentity->locality );
    }

    public function testInvoiceRequestNLReturnsCorrectAddress() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(WebPayItem::individualCustomer()
                        ->setBirthDate(1955, 03, 07)
                        ->setName("Sneider", "Boasman")
                        ->setStreetAddress("Gate 42", "23")     // result of splitStreetAddress w/Svea testperson
                        ->setCoAddress(138)
                        ->setLocality("BARENDRECHT")
                        ->setZipCode("1102 HG")
                        ->setInitials("SB")
                    )
                    ->setCountryCode("NL")
                    ->setCustomerReference("33")
                    ->setOrderDate("2012-12-12")
                    ->setCurrency("SEK")
                    ->useInvoicePayment()
                        ->doRequest();

        $this->assertEquals(1, $request->accepted);
        $this->assertTrue( $request->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );
        // verify returned address
        $this->assertEquals( "Sneider Boasman", $request->customerIdentity->fullName );
        $this->assertEquals( "Gate 42", $request->customerIdentity->street );
        $this->assertEquals( "23", $request->customerIdentity->houseNumber );
        $this->assertEquals( "1102 HG", $request->customerIdentity->zipCode );
        $this->assertEquals( "BARENDRECHT", $request->customerIdentity->locality );

        //<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://webservices.sveaekonomi.se/webpay">
        //  <SOAP-ENV:Body>
        //    <ns1:CreateOrderEu>
        //      <ns1:request>
        //        <ns1:Auth>
        //          <ns1:ClientNumber>85997</ns1:ClientNumber>
        //          <ns1:Username>hollandtest</ns1:Username>
        //          <ns1:Password>hollandtest</ns1:Password>
        //        </ns1:Auth>
        //        <ns1:CreateOrderInformation>
        //          <ns1:OrderRows>
        //            <ns1:OrderRow>
        //              <ns1:ArticleNumber>1</ns1:ArticleNumber>
        //              <ns1:Description>Product: Specification</ns1:Description>
        //              <ns1:PricePerUnit>100</ns1:PricePerUnit>
        //              <ns1:PriceIncludingVat>false</ns1:PriceIncludingVat>
        //              <ns1:NumberOfUnits>2</ns1:NumberOfUnits>
        //              <ns1:Unit>st</ns1:Unit>
        //              <ns1:VatPercent>25</ns1:VatPercent>
        //              <ns1:DiscountPercent>0</ns1:DiscountPercent>
        //            </ns1:OrderRow>
        //          </ns1:OrderRows>
        //          <ns1:CustomerIdentity>
        //            <ns1:Email>
        //            </ns1:Email>
        //            <ns1:PhoneNumber>
        //            </ns1:PhoneNumber>
        //            <ns1:IpAddress>
        //            </ns1:IpAddress>
        //            <ns1:FullName>Sneider Boasman</ns1:FullName>
        //            <ns1:Street>Gate 42</ns1:Street>
        //            <ns1:CoAddress>138</ns1:CoAddress>
        //            <ns1:ZipCode>1102 HG</ns1:ZipCode>
        //            <ns1:HouseNumber>23</ns1:HouseNumber>
        //            <ns1:Locality>BARENDRECHT</ns1:Locality>
        //            <ns1:CountryCode>NL</ns1:CountryCode>
        //            <ns1:CustomerType>Individual</ns1:CustomerType>
        //            <ns1:IndividualIdentity>
        //              <ns1:FirstName>Sneider</ns1:FirstName>
        //              <ns1:LastName>Boasman</ns1:LastName>
        //              <ns1:Initials>SB</ns1:Initials>
        //              <ns1:BirthDate>19550307</ns1:BirthDate>
        //            </ns1:IndividualIdentity>
        //          </ns1:CustomerIdentity>
        //          <ns1:OrderDate>2012-12-12</ns1:OrderDate>
        //          <ns1:AddressSelector>
        //          </ns1:AddressSelector>
        //          <ns1:CustomerReference>33</ns1:CustomerReference>
        //          <ns1:OrderType>Invoice</ns1:OrderType>
        //        </ns1:CreateOrderInformation>
        //      </ns1:request>
        //    </ns1:CreateOrderEu>
        //  </SOAP-ENV:Body>
        //</SOAP-ENV:Envelope>
    }

    public function testInvoiceRequestNLReproduceErrorIn471193() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $request = WebPay::createOrder($config)
                    ->addOrderRow(TestUtil::createOrderRow())
                    ->addCustomerDetails(WebPayItem::individualCustomer()
                        ->setBirthDate(1955, 03, 07)    // BirthDate and ZipCode is sufficient for a successful test order
                        ->setZipCode("1102 HG")         //
                        ->setName("foo", "bar")
                        ->setStreetAddress("foo", "bar")
                        ->setCoAddress(1337)
                        ->setLocality("dns")
                        ->setInitials("nsl")
                    )
                    ->setCountryCode("NL")
                    ->setCustomerReference("33")
                    ->setOrderDate("2012-12-12")
                    ->setCurrency("SEK")
                    ->useInvoicePayment()
                        ->doRequest();
                        //->prepareRequest();
                        //var_dump($request->request->CreateOrderInformation->CustomerIdentity);

        $this->assertEquals(1, $request->accepted);
        $this->assertTrue( $request->customerIdentity instanceof Svea\WebService\CreateOrderIdentity );

        //print_r( $request->sveaOrderId);
        // verify returned address is wrong
        $this->assertNotEquals( "Sneider Boasman", $request->customerIdentity->fullName );
        $this->assertNotEquals( "Gate 42", $request->customerIdentity->street );
        $this->assertNotEquals( "23", $request->customerIdentity->houseNumber );
        $this->assertNotEquals( "BARENDRECHT", $request->customerIdentity->locality );
        //$this->assertNotEquals( "1102 HG", $request->customerIdentity->zipCode );

        $this->assertEquals( "foo bar", $request->customerIdentity->fullName );
        $this->assertEquals( "foo", $request->customerIdentity->street );
        $this->assertEquals( "bar", $request->customerIdentity->houseNumber );
        $this->assertEquals( "dns", $request->customerIdentity->locality );
        $this->assertEquals( "1102 HG", $request->customerIdentity->zipCode );



        //<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://webservices.sveaekonomi.se/webpay" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
        //  <SOAP-ENV:Body>
        //    <ns1:CreateOrderEu>
        //      <ns1:request>
        //        <ns1:Auth>
        //          <ns1:ClientNumber>85997</ns1:ClientNumber>
        //          <ns1:Username>hollandtest</ns1:Username>
        //          <ns1:Password>hollandtest</ns1:Password>
        //        </ns1:Auth>
        //        <ns1:CreateOrderInformation>
        //          <ns1:ClientOrderNumber>133</ns1:ClientOrderNumber>
        //          <ns1:OrderRows>
        //            <ns1:OrderRow>
        //              <ns1:ArticleNumber>NTB03</ns1:ArticleNumber>
        //              <ns1:Description>Making candles and soaps for dummies: Making candles and soaps for dummies</ns1:Description>
        //              <ns1:PricePerUnit>12.12</ns1:PricePerUnit>
        //              <ns1:PriceIncludingVat xsi:nil="true" />
        //              <ns1:NumberOfUnits>2</ns1:NumberOfUnits>
        //              <ns1:Unit>st</ns1:Unit>
        //              <ns1:VatPercent>25</ns1:VatPercent>
        //              <ns1:DiscountPercent>0</ns1:DiscountPercent>
        //            </ns1:OrderRow>
        //            <ns1:OrderRow>
        //              <ns1:ArticleNumber>SHIP25</ns1:ArticleNumber>
        //              <ns1:Description>Frakt / Shipping</ns1:Description>
        //              <ns1:PricePerUnit>8.66</ns1:PricePerUnit>
        //              <ns1:PriceIncludingVat xsi:nil="true" />
        //              <ns1:NumberOfUnits>1</ns1:NumberOfUnits>
        //              <ns1:Unit>st</ns1:Unit>
        //              <ns1:VatPercent>25</ns1:VatPercent>
        //              <ns1:DiscountPercent>0</ns1:DiscountPercent>
        //            </ns1:OrderRow>
        //            <ns1:OrderRow>
        //              <ns1:ArticleNumber>HAND25</ns1:ArticleNumber>
        //              <ns1:Description>Expeditionsavgift / Handling</ns1:Description>
        //              <ns1:PricePerUnit>2.51</ns1:PricePerUnit>
        //              <ns1:PriceIncludingVat xsi:nil="true" />
        //              <ns1:NumberOfUnits>1</ns1:NumberOfUnits>
        //              <ns1:Unit>st</ns1:Unit>
        //              <ns1:VatPercent>25</ns1:VatPercent>
        //              <ns1:DiscountPercent>0</ns1:DiscountPercent>
        //            </ns1:OrderRow>
        //          </ns1:OrderRows>
        //          <ns1:CustomerIdentity>
        //            <ns1:Email>
        //            </ns1:Email>
        //            <ns1:PhoneNumber>
        //            </ns1:PhoneNumber>
        //            <ns1:IpAddress>
        //            </ns1:IpAddress>
        //            <ns1:FullName>asdf ghij</ns1:FullName>
        //            <ns1:Street>Postbus</ns1:Street>
        //            <ns1:CoAddress>
        //            </ns1:CoAddress>
        //            <ns1:ZipCode>1010 AB</ns1:ZipCode>
        //            <ns1:HouseNumber>626</ns1:HouseNumber>
        //            <ns1:Locality>Amsterdam</ns1:Locality>
        //            <ns1:CountryCode>NL</ns1:CountryCode>
        //            <ns1:CustomerType>Individual</ns1:CustomerType>
        //            <ns1:IndividualIdentity>
        //              <ns1:FirstName>asdf</ns1:FirstName>
        //              <ns1:LastName>ghij</ns1:LastName>
        //              <ns1:Initials>ag</ns1:Initials>
        //              <ns1:BirthDate>19550307</ns1:BirthDate>
        //            </ns1:IndividualIdentity>
        //          </ns1:CustomerIdentity>
        //          <ns1:OrderDate>2014-11-19</ns1:OrderDate>
        //          <ns1:AddressSelector>
        //          </ns1:AddressSelector>
        //          <ns1:OrderType>Invoice</ns1:OrderType>
        //        </ns1:CreateOrderInformation>
        //      </ns1:request>
        //    </ns1:CreateOrderEu>
        //  </SOAP-ENV:Body>
        //</SOAP-ENV:Envelope>

    }

    function test_orderRow_discountPercent_not_used() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $orderResponse = WebPay::createOrder($config)
                ->addOrderRow(
                        WebPayItem::orderRow()
                        ->setAmountExVat(100.00)
                        ->setVatPercent(25)
                        ->setQuantity(1)
                )
                ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                ->setCountryCode("SE")
                ->setOrderDate("2012-12-12")
                ->useInvoicePayment()->doRequest();
        $this->assertEquals(1, $orderResponse->accepted);
        $this->assertEquals("125.00", $orderResponse->amount);
        //print_r($orderResponse);

        $query = WebPayAdmin::queryOrder($config)
                ->setCountryCode('SE')
                ->setOrderId($orderResponse->sveaOrderId)
                ->queryInvoiceOrder()->doRequest();
        $this->assertEquals(1, $query->accepted);
        $this->assertEquals(100.00, $query->numberedOrderRows[0]->amountExVat);
        $this->assertEquals(25.00, $query->numberedOrderRows[0]->vatPercent);
        $this->assertEquals(0.00, $query->numberedOrderRows[0]->discountPercent);
    }

    function test_orderRow_discountPercent_50percent() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $orderResponse = WebPay::createOrder($config)
                ->addOrderRow(
                        WebPayItem::orderRow()
                        ->setAmountExVat(100.00)
                        ->setVatPercent(25)
                        ->setQuantity(1)
                        ->setDiscountPercent(50)
                )
                ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                ->setCountryCode("SE")
                ->setOrderDate("2012-12-12")
                ->useInvoicePayment()->doRequest();
        $this->assertEquals(1, $orderResponse->accepted);
        $this->assertEquals("62.50", $orderResponse->amount);

        $query = WebPayAdmin::queryOrder($config)
                ->setCountryCode('SE')
                ->setOrderId($orderResponse->sveaOrderId)
                ->queryInvoiceOrder()->doRequest();
        $this->assertEquals(1, $query->accepted);
        $this->assertEquals(100.00, $query->numberedOrderRows[0]->amountExVat);
        $this->assertEquals(25.00, $query->numberedOrderRows[0]->vatPercent);
        $this->assertEquals(50.00, $query->numberedOrderRows[0]->discountPercent);
    }

    function test_orderRow_discountPercent_50_percent_order_sent_as_incvat() {
        $config = Svea\SveaConfig::getDefaultConfig();
        $orderResponse = WebPay::createOrder($config)
                ->addOrderRow(
                        WebPayItem::orderRow()
                        ->setAmountIncVat(125.00)
                        ->setVatPercent(25)
                        ->setQuantity(1)
                        ->setDiscountPercent(50)
                )
                ->addCustomerDetails(TestUtil::createIndividualCustomer("SE"))
                ->setCountryCode("SE")
                ->setOrderDate("2012-12-12")
                ->useInvoicePayment()->doRequest();
        $this->assertEquals(1, $orderResponse->accepted);
        $this->assertEquals("62.50", $orderResponse->amount);   // this is where

        $query = WebPayAdmin::queryOrder($config)
                ->setCountryCode('SE')
                ->setOrderId($orderResponse->sveaOrderId)
                ->queryInvoiceOrder()->doRequest();
        $this->assertEquals(1, $query->accepted);
        $this->assertEquals(125.00, $query->numberedOrderRows[0]->amountIncVat);
        $this->assertEquals(25.00, $query->numberedOrderRows[0]->vatPercent);
        $this->assertEquals(50.00, $query->numberedOrderRows[0]->discountPercent);
    }
}