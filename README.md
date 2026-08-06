# Magelearn_AssignOrder
This extension adds an Assign Customer popup to the Sales Order View page in the Magento Admin, allowing you to assign a guest order to an existing customer.

When you click Assign Customer, a popup opens displaying a customer grid. Simply search for and select the appropriate customer, then assign the guest order to that customer.

If the wrong customer is assigned by mistake, you can easily correct it using the provided CLI command, which allows you to reassign the order to a different customer.

## CLI Command

-- Magento CLI command for reassigning an order to a different customer

php bin/magento magelearn:order:customer:reassign <order_increment_id> <customer_id>

-- Magento CLI command for Check and optionally correct order state and status

php bin/magento magelearn:order:check

## How to Install
-- Download the Zip and Create a folder inside app/code/Magelearn/AsignOrder in your Magento Root Directory.

-- Extract the Zip and Run Magento Commands.

## Screenshots

![Place guest order](/assets/step_1.png "Place guest order")

![click assign customer](/assets/step_2.png "click assign customer")

![ordder comment](/assets/step_3.png "ordder comment")

