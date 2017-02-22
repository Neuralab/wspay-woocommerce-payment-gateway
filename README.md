# WSPay WooCommerce Payment Gateway


## Synopsis

This WSPay / WooCommerce plugin is developed having our “Clean & Simple” plugin philosophy in mind - only rudimentary lines of code are included with zero tolerance to bloat or unnecessary function. Currently the plugin works on WSPay redirect principle, but we are working on publishing a version 2.0 that will include inline form for credit card processing. On a side note, this plugin is provided “as-is” and we don't currently provide support around installing and optimizing it for your needs. Look at it like a starting ground for WSPay processing.

## Motivation

“Show me the money!” is a fairly good movie quote, summarizing thoughts for every business owner. It’s even more powerful in eCommerce world where store owner needs to tackle various foggy and uncertain concepts like User eXperience, User Interactions, sales funnels, big data analytics, A/B testing and even  “what the hell is CMS”.

![alt text](https://s3.amazonaws.com/neuralabostalo/GitHub-Static-Files/aaron-burden-smiling-guy-700.jpg "A/B testing?!... are you kiddin’ me. I’m doing terabyte scale real time optimization… It’s called “life” boyo.")

Payment gateways are amongst this foggy digital concepts. Everybody “knows” that they are needed, brands like PayPal are publicly known, but nobody understands what they actually do. Let’s clear that right of the table…

> internet payment gateways are a mandatory part of every web store, in charge of processing credit cards so that the customer order is fully paid.

Their full scope lies in managing the complete online payment process - taking credit card info on the checkout page > processing credit card and removing funds to cover the customer order > taking funds and placing it in store owner merchant account > transferring funds from merchant account to store owner's bank account. In this last step they really “show you the money” :)

![alt text](https://s3.amazonaws.com/neuralabostalo/GitHub-Static-Files/IPG-Market-Segmentation-S3-700.jpg)

Payment gateways have a lot of options - they can process various forms of payments like Bitcoins, debit cards, bank accounts and of course, credit cards. They can also process payments in rates, phases or even with a postponement. As the market on various technologies grew, so was the field of Internet Payment Gateways (IPG). Their business model is simple - they will take a small percent from every transaction that you as a store owner process. In that way, their service to you as a store owner is pretty straightforward - if you don't make money and transactions on your web shop, the gateway will not take any money from you, but if you have a lot of transactions, gateway will take percentage from every transaction and earn revenue for its own operations. Gateway percentages are usually around 1 to 3 %, but they vary in scope and type of percent, fixed or variable fee.

One of the important questions for every IPG is the ability to send collected funds to store owner's bank account. It’s reasonable to think of this process as the most important part of the whole eCommerce process. This is the phase where the owner has made the sale, charged customer credit card, shipped the product, but it's still waiting for the money! It’s reasonable for every store owner to be on pins & needles and get the real money. That’s where the local internet payment gateways have their advantage - they can fastly transfer funds from their account to local bank account. 

![alt text](https://s3.amazonaws.com/neuralabostalo/GitHub-Static-Files/fabian-blank-pig-700.jpg "Don’t think twice, fat bank account is the primary goal for every business.")

One of the good Internet payment gateways in Croatia is WS Pay, an IPG with a fair amount of experience in eCommerce payments field. Their platform can process credit cards, debit cards, take rates and payments in phases and is specifically geared towards Croatian eCommerce market as they can easily push funds to Croatian store owner's bank account. Also, local support via email, ticketing system or phone is always a good thing when dealing with real money. Having built over dozen large eCommerce systems in Croatia, we generally always recommend WSPay when implementing Internet Payment Gateways. This is the reason why we decided to develop and freely OpenSource our own custom plugin that integrates WooCommerce with WSPay system (LINK TO GITHUB). Note that you will need at least a demo WSPay account to make this work. 

## Installation

Install this plugin like any other WordPress plugin. Open up your demo account with WSPay and ask for secret keys and additional merchant info that plujgin requires. Manual setup is mandatory as WSPay needs to open up your account in non-automatic fashion.

## Tests

You will need a demo WSPay account to test the V.1.0 of this software. Structured Unit tests are planned for version V2.0

## Contributors

Software is currently maintained by selected members of Neuralab team - Matej Basic, Ivan Brkic and Slobodan Alavanja

## License

This software is provided “as-is” and we don't currently provide support around installing and optimizing it for your needs. Look at it like a starting ground for WSPay processing. Here is the full MIT licence: 

MIT License

Copyright (c) 2017 Neuralab Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.


