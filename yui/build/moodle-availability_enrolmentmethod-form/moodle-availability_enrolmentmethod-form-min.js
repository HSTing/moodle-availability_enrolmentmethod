YUI.add("moodle-availability_enrolmentmethod-form",function(e,t){M.availability_enrolmentmethod=M.availability_enrolmentmethod||{},M.availability_enrolmentmethod.form=e.Object(M.core_availability.plugin),M.availability_enrolmentmethod.form.enrolmentmethods=null,M.availability_enrolmentmethod.form.initInner=function(e){this.enrolmentmethods=e},M.availability_enrolmentmethod.form.getNode=function(t){for(var i='<label><span class="pr-3">'+M.util.get_string("title","availability_enrolmentmethod")+'</span> <span class="availability-enrolmentmethod"><select name="id" class="custom-select"><option value="choose">'+M.util.get_string("choosedots","moodle")+"</option>",o=0;o<this.enrolmentmethods.length;o++){var l=this.enrolmentmethods[o];i+='<option value="'+l.id+'">'+l.name+"</option>"}i+="</select></span></label>";var a=e.Node.create('<span class="form-inline">'+i+"</span>");return void 0===t.creating&&(void 0!==t.id&&a.one("select[name=id] > option[value="+t.id+"]")?a.one("select[name=id]").set("value",""+t.id):void 0===t.id&&a.one("select[name=id]").set("value","any")),!M.availability_enrolmentmethod.form.addedEvents&&(M.availability_enrolmentmethod.form.addedEvents=!0,e.one(".availability-field").delegate("change",function(){M.core_availability.form.update()},".availability_enrolmentmethod select")),a},M.availability_enrolmentmethod.form.fillValue=function(e,t){var i=t.one("select[name=id]").get("value");"choose"===i?e.id="choose":"any"!==i&&(e.id=parseInt(i,10))},M.availability_enrolmentmethod.form.fillErrors=function(e,t){var i={};this.fillValue(i,t),i.id&&"choose"===i.id&&e.push("availability_enrolmentmethod:error_selectenrolmentmethod")}},"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});