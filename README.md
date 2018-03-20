com.paperlesstrans.civicrm
===============
CiviCRM Extension for Paperless Transaction Corporation Payment Processor 

This README.md contains information specific to system administrators/developers. 

Requirements
------------

1. CiviCRM 4.6.x or 4.7.x. We strongly recommend that you keep up with the most recent version of each branch.

2. PHP needs to include the SOAP extension (php.net/manual/en/soap.setup.php). Recommend that you use at least PHP 5.6 but 5.3 and above should work if it supports TLS1.1/1.2 and SHA-256.

3. To use this extension in production, you must have a Paperless Transaction's Payments Account and have configured it to accept payment though WebServices. You can use the shared test account credentials for initial setup and testing. To know more about the Paperless Transaction Corporation's BackOffice API, please see the Documentation Resource: http://apidocs.paperlesstrans.com/api-overview.php

4. To handle ACH/EFT contribution verification and to handle Recurring Contributions (of any type) you must configure scheduled jobs through a cron job for your CiviCRM install. Information about how to do this can be found in: http://wiki.civicrm.org/confluence/display/CRMDOC/Managing+Scheduled+Jobs.

Installation
------------

This extension follows the standard CiviCRM extension installation method. Download and unpack the extension to your configured extension director, browse to the extension manager in the CiviCRM interface, refresh the list and install the 'Paperless Transaction Payment Processor (com.paperlesstrans.civicrm)' extension.

If you need help with installing extensions, try: https://wiki.civicrm.org/confluence/display/CRMDOC/Extensions

If you want to try out a particular version directly from github, you probably already know how to do that.

Once the extension is installed, you need to add the payment processor(s) and input your Paperless Transaction Corporation's credentials:

1. Administer -> System Settings -> Payment Processors -> + Add Payment Processor

2. Select Paperless Transactions Credit Card or Paperless Transactions ACH/ETF, they are provided by this extension (the instructions differ only slightly for each one). You can create multiple payment processor entries using the same credentials for the different types.

3. The "Name" of the payment processor is what your site visitors will see when they select a payment method, so typically use "Credit Card" here, or "Credit Card C$" (or US$) if there's any doubt about the currency. 

4. The test account uses Username = 18304896-329f-4b2e-a6e4-b39157dafeda and Password = 390489817. This is a shared test account, so don't put in any private information.

5. If you'd like to test using live workflows, you can temporarily use the test account credentials in your live processor fields.

6. Create a Contribution Page (or go to an existing one) -> Under Configure -> Contribution Amounts -> select your newly installed/configured Payment Processor(s), and Save.

7. (Very Important Verification)
Please make sure that you have downloaded the extension under the "../civicrm/ext" directory inside the server root so as to ensure secure transaction to be enabled. Otherwise, a TypeError will be shown in the browser's console and the extension cannot take transactions starting from a future date.

Extension Testing Notes
-----------------------

1. Manage Contribution Pages -> Links -> Live Page.

  * Paperless Transactions Payments Credit Card: use test VISA: 4012888888881881 security code = 123 and any future Expiration date - to process any $amount.

  * Paperless Transactions Payments ACH/EFT: use 111111118 for the Bank Identification Number and 12121214 for Bank Account Number along with any bank name and account holder.

2. After completing a TEST payment

  * If you have selected a future date, check Administer -> System Settings -> Scheduled Jobs to find your transaction there waiting to execute at the future start date you mentioned.
  * Otherwise, check the Contributions -> Dashboard. 

3. If things don't look right, you can turn on Drupal and CiviCRM logging - try another TEST transaction - and then see some detailed logging of the SOAP exchanges for more hints about where it might have gone wrong.

4. To test recurring contributions - try creating a recurring contribution for every day and then go back the next day and manually trigger Scheduled Job.

Once tested using the test credentials, update the Payment Processor record(s) with your own Paperless Transaction's Login credentials.

Remember to turn off debugging/logging on any production environment.

Issues & Limitations 
--------------------

Some issues may be related to core CiviCRM issues, and may not have an immediate solution, but we'll endeavour to help you understand, work-around, and/or fix whatever concerns you raise on the issue queue.

Limitations of using future date feature:

* The future date feature will automatically be enabled for both one-time/recurring contributions when Paperless Transactions' Payment Processor is used on a Contribution Page.
* Custom fields are not passed into the future date feature on the Contribution Page. For any modification to the Contribution Page's front end layout or form fields, please reach out to us at mhussain@paperlesstrans.com and we'll send you an email with those features to add into your form.

Please post an issue to the github repository if you have any questions.
