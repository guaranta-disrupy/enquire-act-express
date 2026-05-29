import vuex from 'vuex';
export default (function (global, factory) {
    
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory(vuex) :
    typeof define === 'function' && define.amd ? define(['vuex'], factory) :
    (global.vueGlobalVariable = factory(global.Vuex));
}(this, (function (Vuex) { 'use strict';

    Vuex = Vuex && Vuex.hasOwnProperty('default') ? Vuex['default'] : Vuex;

    var VueGlobalVariable = {
        install: (app, options) => {
            options = options || {};
            const store = options.store || {state:{}}
            app.use(Vuex);
            // var store = new Vuex.Store({ state: {} });
            var globals = options.globals || {};
            Object.keys(globals).forEach(function (key) {
                window["" + key] = globals[key];
                //Vue.set(store.state, key, globals[key]);
                store.state[key] = globals[key];
            });
            var computed = {};
            Object.keys(globals).forEach(function (key) {
                computed["" + key] = function () { return globals[key]; };
            });
            app.mixin({
                computed: computed
            });
        }
    };

    return VueGlobalVariable;

})));