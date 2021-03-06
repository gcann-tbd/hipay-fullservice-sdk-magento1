/**********************************************************************************************
 *
 *                              TEST CHECKOUT IN ADMIN : HOSTED
 *
 *  To launch test, please pass two arguments URL (BASE URL)  and TYPE_CC ( CB,VI,MC )
 *
 /**********************************************************************************************/
var paymentType = "HiPay Enterprise Hosted Page MO/TO",
    currentBrandCC = typeCC;

casper.test.begin('Test Checkout ' + paymentType + ' with ' + typeCC, function(test) {
    phantom.clearCookies();

    casper.start(baseURL)
    .then(function() {
        this.waitUntilVisible('div.footer', function success() {
            if (this.exists('p[class="bugs"]')) {
                test.done();
            }
        }, function fail() {
            test.assertVisible("div.footer", "'Footer' exists");
        }, 10000);
    })
    .thenOpen(baseURL + 'admin', function() {
        this.logToBackend();
        configuration.proceedMotoSendMail(test, '0');
        method.proceed(test, paymentType, "hostedmoto");

    },15000)
    .then(function() {
        checkout.proceed(test, paymentType, "hostedmoto");
    })
    .then(function() {
        this.echo("Submitting order...", "INFO");
        this.waitForSelector(x('//span[text()="Submit Order"]'), function success() {
            this.click(x('//span[text()="Submit Order"]'));
            test.info("Done");
        }, function fail() {
            test.assertExists(x('//span[text()="Submit Order"]'), "Submit order button exists");
        });
    })
    /* payment method selection and filling */
    .then(function() {
        this.waitForUrl(/payment\/web\/pay/, function success() {
            pay.proceed(test);
            this.then(function() {
                this.echo("Checking order success...", "INFO");
                this.waitForUrl(/admin\/sales_order\/view\/order_id/, function success() {
                    test.assertHttpStatus(200, "Correct HTTP Status Code 200");
                    test.assertExists(x('//span[text()="The order has been created."]'), "The order has been successfully placed with method " + paymentType + " !");
                }, function fail() {
                    test.assertUrlMatch(/admin\/sales_order\/view\/order_id/, "Hosted payment page exists");
                }, 20000);
            });
        }, function fail() {
            test.assertUrlMatch(/payment\/web\/pay/, "Hosted payment page exists");
        },10000);
    })
    .run(function() {
        test.done();
    });
});
