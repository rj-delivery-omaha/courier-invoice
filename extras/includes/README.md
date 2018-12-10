# Integration Configuration

[Jump to user_functions](https://github.com/rjdeliveryomaha/courierinvoice/tree/master/extras/includes#user_functions)

### username

String

Your Courier Invoice account number.

### publicKey

String

Public API key. This can be found on the reconfigure page at [courierinvoice.com](https://rjdeliveryomaha.com/courierinvoice.com)

### privateKey

String

Private API key. This can be found on the reconfigure page at [courierinvoice.com](https://rjdeliveryomaha.com/courierinvoice.com)

### enableLogging

Boolean

Indicates weather or not to log errors.

### targetFile

String

File location for logging. If enableLogging is ``` true ``` and this value is NULL or an empty string an error will be thrown.

### showCancelledTicketsOnInvoiceExceptions

Indexed array

Canceled tickets are not displayed on invoices by default.

This array may contain client ID numbers to exclude from this behavior.

### consolidateContractTicketsOnInvoiceExceptions

Contract tickets are consolidated for display on invoices by default.

This array may contain client ID numbers to exclude from this behavior.

### clientNameExceptions

Associative array

Client names that should be changed, for example, to abbreviate.

Ex: ``` [ 'some long client name' => 'SLCN'] ```

### clientAddressExceptions

Indexed array

Addresses that should be ignored, for example, due to change of address.

### ignoreValues

Indexed array

Values that should not be included on ticket entry datalists. Values should be lower case.


### emailConfig

Associative array

Setting to use with [PHPMailer](https://github.com/PHPMailer/PHPMailer/tree/6.0).

Keys:

  - fromAddress

  - password

  - smtpHost

  - secureType

    This can be either 'ssl' or 'tls'

  - port

    This will vary based upon which 'secureType' is chosen

  - fromName

  - BCCAddress

### allTimeChartLimit

Integer

Maximum number of months to display on a chart. Default is 6.

### userLogin

String

Login name for Courier Invoice user as an alternative to using client ID 0 (zero).

### driverChargesEntryExclude

Indexed array

By default all charges are included on ticket forms.

This setting removes charges for drivers ticket entry and update form.

Index 0 driver can dispatch to self.

Index 1 driver can dispatch to all.

### driverChargesQueryExclude

Indexed array

This setting removes charges for drivers ticket query form.

Index 0 driver can dispatch to self.

Index 1 driver can dispatch to all.

### dispatchChargesEntryExclude

Indexed array

This setting removes charges for dispatchers ticket entry and update form.

### dispatchChargesQueryExclude

Indexed array

This setting removes charges for dispatchers ticket query form.

### clientChargesEntryExclude

Indexed array

This setting removes charges for clients ticket entry (request) form.

Index 0 admin clients.

Index 1 daily clients.

### clientChargesQueryExclude

Indexed array

This setting removes charges for client ticket query form.

Index 0 admin clients.

Index 1 daily clients.

### orgChargesQueryExclude

Indexed array

This setting removes charges for organizations ticket query form.

### client0ChargesEntryExclude

Indexed array

This setting removes charges for Courier Invoice user client 0 ticket entry and update form.

### client0ChargesQueryExclude

Indexed array

This setting removes charges for Courier Invoice user client 0 ticket query form.

### initialCharge

Integer

By default the Charge property is null when the ticket entry form is initialized.

This setting selects a Charge value the ticket entry form is initialized.

### extend

Associative array

Extend functionality with custom menu items, pages, and javascript.

The top level keys are who to create the items for; all, client, org, driver, dispatcher, client0.

Entries are indexed arrays with the following content:

```php

[ 'Menu Item', 'function_name', '../path/to/javascript.js', 'jsAttribute', 'jsAttribute' ]

```

Index 0 will be added, as is, to the menu. It will then have any HTML tags striped, be converted to lowercase, spaces replaced with underscore and used as the id attribute of the page.

Index 1, if set and not null or an empty string, will be looked for first as a method in the Ticket, Route, Invoice, and Client classes then as a function in includes/user_functions.php to populate the page.

Index 2, if set and not null or an empty string, will be added as the src of a script element.

If an entry has a non-null, not empty string at index 0 and a null or empty string at index 1 it will be moved to the end of the list. This is done to preserve the indexing of entries to pages.

If both index 0 and 1 are null or empty string index 2 will be added as the src of a script element. All scripts are added to the page in the order they are encountered in this configuration.

Any indices beyond 2 will be interpreted as attributes to be applied to the script for example defer or async.

  - __all__

    Indexed array of settings entries as described above.

    These items will be added for all users.

  - __client__

    Indexed array with three indices.

    Each index is an indexed array of settings entries as described above.

    * 0: All clients.

    * 1: Admin clients.

    * 2: Daily user clients.

    These entries are not applied to organization users.

    These entries are not applied to Courier Invoice user client ID 0 (zero).

  - __org__

    Indexed array of settings entries as described above.

    These items will be added for only organization users.

  - __driver__

    Indexed array with three indices.

    Each index is an indexed array of settings entries as described above.

    * 0: All drivers.

    * 1: Drivers that cannot dispatch.

    * 2: Drivers that can dispatch only to themselves.

    * 3: Drivers that can dispatch to themselves and other.

  - __dispatch__

    Indexed array of settings entries as described above.

  - __client0__

    Indexed array of settings entries as described above.

---

Providing your basic Courier Invoice configuration options is necessary when using the CommonFunctions class without a session. An example is provided at the end of [api_config.php](https://github.com/rjdeliveryomaha/courierinvoice/blob/master/extras/includes/api_config.php).

---

# user_functions