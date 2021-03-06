/**
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind
 */
if (!(function () {}).bind) {
    Function.prototype.bind = function (oThis) {
        if (typeof this !== "function") {
            // closest thing possible to the ECMAScript 5
            // internal IsCallable function
            throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
        }

        var slice = [].slice,
            aArgs = slice.call(arguments, 1),
            fToBind = this,
            prototype = fToBind.prototype;

        function NOP() {
        }

        function Bound() {
            return fToBind.apply(this instanceof NOP ? this : oThis,
                aArgs.concat(slice.call(arguments))
            );
        }

        if (prototype) {
            // Function.prototype doesn't have a prototype property
            NOP.prototype = prototype;
        }
        Bound.prototype = new NOP();

        return Bound;
    };
}
