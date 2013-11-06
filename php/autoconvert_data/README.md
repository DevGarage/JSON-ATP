JSON-ATP php client side
========


The main function of the client is to create an encrypted string and send it to the server.

The structure of the incoming lines:
HEAD - the first 4 bytes in HEX format. Which contain the length of the incoming message Head.

Flag - the fifth byte in HEX format.
Which contains information encryption and compression, the eighth bit - is responsible for compression,
the seventh bit - encryption, the first six bit - reserve.

After six bytes is part of the HEAD.
Head code in Base64 (AES-128-CBC).
Head - It contains the following parameters:
Id - ID of the client.
Pid - the identifier of the project.
Signature - data and text data in encrypted format sha256 (sha256 (data, text)).
Time - timeshtamp send time about the time zone GMT +0.
Size - Length of Data.
Cipher - type of encryption OPENSSL.
If not specified, chipper, then takes the default method.

After part HEAD  is part of the DATA.

The file itself has two functions:
encode - Getting strings.Na input is a line of input , which is decoded by the following algorithm :
Decrypted with the public key to BASE64 ( located on the customer id ) .
Finding the length of the header in the first four bytes (transfer from HEX format to Dec).
Finding guidance on the compression and encryption ( for the above format ) .
Getting header HEAD. ( Structure listed above).
Then follows a portion of DATA. Encrypted data.
DATA - need to decrypt :
Decrypted using the private key for OPENSSL ( located in the parameter chipper).
Decompression of the text.
Translations of text in JSON.
And returns JSON development area .


dencode - Input is a JSON object , and the client id .
Text is encoded into a sequence :
 JSON translated into text.
Compression tex .
Encryption using a private key for OPENSSL.
HEAD formed lines at the above format.
FLAG lines formed according to the above format.
Encryption using the public key to BASE64 (AES-128-CBC ) .
And returns the encrypted string.

An error occurred and the program returns FALSE, and an error code.