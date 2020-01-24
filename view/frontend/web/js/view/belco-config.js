define([
  'uiComponent',
  'Magento_Customer/js/customer-data'
], function (Component, customerData) {
  'use strict';

  function loadWidget(callback) {
    !function(n,o){var e=window.belcoFunction||"Belco";window[e]||(window[e]=function(n){if(void 0===window[e][n])throw new Error("Unknown method");return window[e][n].apply(window[e],Array.prototype.slice.call(arguments,1))}),window[e]._q=[];for(var i=["init","sync","track","page","open","close","toggle","on","once","off","anonymousId","customer","reset","sendMessage"],t=function(n){return function(){var o=Array.prototype.slice.call(arguments);return o.unshift(n),window[e]._q.push(o),window[e]}},w=0;w<i.length;w++){var r=i[w];window[e][r]=t(r)}window[e].load=function(e){if(!n.getElementById("belco-js")){var i=n.createElement(o);i.async=!0,i.id="belco-js",i.type="text/javascript",i.src="//cdn.belco.io/v2/widget.js",i.onload=function(n){"function"==typeof e&&e(n)};var t=n.getElementsByTagName(o)[0];t.parentNode.insertBefore(i,t)}},window.belcoConfig&&window[e].load(function(){window[e]("init",window.belcoConfig)})}(document,"script");
    Belco.load(callback)
  }

  function initBelco(config) {
    if (config && config.shopId) {
      Belco('init', _.omit(config, 'data_id'));
      Belco.once('ready', function() {
          document.dispatchEvent(new Event('Belco_Ready'));
      })
    }
  }

  return Component.extend({
    initialize: function () {
      var self = this;

      this._super();

      loadWidget(function() {
        var data = customerData.get('belco-config');
        
        data.subscribe(initBelco);

        if (!data().shopId) {
          customerData.reload(['belco-config'], false);
        } else {
          initBelco(data());
        }
      })
    }
  });
});
