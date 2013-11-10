/**
 * Json Advanced transport protocol
 *
 * DevGar
 * Ukraine, Odessa
 * User: Bubelich Nikolay
 * email: thesimj@gmail.com
 * GitHub: https://github.com/DevGarage/JSON-ATP.git
 * Date: 06.11.13
 * Time: 9:06
 *
 * Use ----
 * crypto-js
 * JavaScript implementations of standard and secure cryptographic algorithms
 * https://code.google.com/p/crypto-js/
 *
 *
 *
 */


var JsonAtp = function(){

    this.ATP_PROTOCOL = 1;

    function getProtocol(){
        return this.ATP_PROTOCOL;
    }

    function sha256(text){
        return CryptoJS.SHA256("Message");
    };
};