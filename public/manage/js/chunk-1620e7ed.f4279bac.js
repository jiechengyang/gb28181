(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-1620e7ed"],{"93a7":function(e,r,t){"use strict";t.r(r);var n=function(){var e=this,r=e.$createElement,t=e._self._c||r;return t("a-modal",{attrs:{title:e.title,width:640,visible:e.visible,confirmLoading:e.loading},on:{ok:function(){e.$emit("ok")},cancel:function(){e.$emit("cancel")}}},[t("a-spin",{attrs:{spinning:e.loading}},[t("a-form-model",e._b({ref:"bindForm",attrs:{model:e.model,rules:e.rules}},"a-form-model",e.formItemLayout,!1),[t("a-form-model-item",{attrs:{label:"录像机",prop:"recorder_id"}},[t("a-select",{attrs:{"show-search":"",placeholder:"请选择录像机"},model:{value:e.model.recorder_id,callback:function(r){e.$set(e.model,"recorder_id",r)},expression:"model.recorder_id"}},e._l(e.nvrItems,(function(r,n){return t("a-select-option",{key:n,attrs:{value:r.id}},[e._v(e._s(r.device_name?r.device_name:r.device_id))])})),1)],1)],1)],1)],1)},o=[],a=t("1da1"),i=(t("96cf"),t("aa98")),c={recorder_id:void 0},s={props:{visible:{type:Boolean,default:!0},loading:{type:Boolean,default:function(){return!1}}},data:function(){return{title:"绑定录像机",model:c,nvrItems:{},formItemLayout:{labelCol:{span:6},wrapperCol:{span:14}},rules:{recorder_id:[{required:!0,message:"录像机必须选择",trigger:"blur"}]}}},methods:{loadnvrItems:function(){var e=this;return Object(a["a"])(regeneratorRuntime.mark((function r(){return regeneratorRuntime.wrap((function(r){while(1)switch(r.prev=r.next){case 0:return r.next=2,Object(i["w"])();case 2:e.nvrItems=r.sent,console.log("===================================="),console.log("nvrItems:",e.nvrItems),console.log("====================================");case 6:case"end":return r.stop()}}),r)})))()},resetForm:function(){this.model.recorder_id=c.recorder_id}},watch:{visible:function(e,r){var t=this;return Object(a["a"])(regeneratorRuntime.mark((function r(){return regeneratorRuntime.wrap((function(r){while(1)switch(r.prev=r.next){case 0:e&&t.loadnvrItems();case 1:case"end":return r.stop()}}),r)})))()}}},l=s,d=(t("f316"),t("2877")),u=Object(d["a"])(l,n,o,!1,null,"b169fc80",null);r["default"]=u.exports},d83e:function(e,r,t){},f316:function(e,r,t){"use strict";t("d83e")}}]);
//# sourceMappingURL=chunk-1620e7ed.f4279bac.js.map