var Device={type:"desktop",width:0,height:0,scrollTop:0,init:function(){this.setDeviceType(),this.setDimentions(),this.setScrollTop(),$(window).resize(function(){Device.setDimentions()}),$(window).scroll(function(){Device.setScrollTop()})},setDeviceType:function(){var t=navigator.userAgent.toLowerCase();t.match(/(iphone|ipod|ipad|android|blackberry)/)?t.match(/(ipad)/)?(this.type="tablet",this.setViewport(this.type)):(this.type="phone",this.setViewport(this.type)):(this.type="desktop",this.setViewport(this.type))},setViewport:function(t){switch(t){case"tablet":break;case"phone":}},setDimentions:function(){this.width=$(window).width(),this.height=$(window).height()},setScrollTop:function(){this.scrollTop=$(window).scrollTop()}};$(function(){Device.init()});