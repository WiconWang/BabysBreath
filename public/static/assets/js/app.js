(function ($) {
    'use strict';

    $(function () {
        var $fullText = $('.admin-fullText');
        $('#admin-fullscreen').on('click', function () {
            $.AMUI.fullscreen.toggle();
        });

        $(document).on($.AMUI.fullscreen.raw.fullscreenchange, function () {
            $fullText.text($.AMUI.fullscreen.isFullscreen ? '退出全屏' : '开启全屏');
        });
    });
})(jQuery);

$(document).ready(function () {
    //角色删除
    $("a[data-ajax='Manager_Role_Del']").click(function () {
        $('#my-confirm').find(".am-modal-hd").html('确认删除这个权限模块么？');
        $('#my-confirm').find(".am-modal-bd").html('此操作不可恢复，请谨慎操作！').show();
        obj = $(this);
        $('#my-confirm').modal({
            relatedTarget: this,
            onConfirm: function(options) {
                $.ajax({
                    url: "/admin/manager/RoleDel",
                    type: "get",
                    dataType: "json",
                    data: {id: obj.attr('data-id')},
                    async: false,
                    success: function (json) {
                        $('#my-alert').find(".am-modal-hd").html(json.msg);
                        $('#my-alert').modal();
                        if (json.status == 1) {
                            obj.parents('tr').remove();
                        }
                    },
                    error: function () {
                        console.log("AJAX fail");
                    }
                });

            },
            // closeOnConfirm: false,
            onCancel: function() {
            }
        });

    });


    //角色删除
    $("a[data-ajax='Manager_Group_Del']").click(function () {
        $('#my-confirm').find(".am-modal-hd").html('确认删除这个用户组么？');
        $('#my-confirm').find(".am-modal-bd").html('此操作不可恢复，请谨慎操作！').show();
        obj = $(this);
        $('#my-confirm').modal({
            relatedTarget: this,
            onConfirm: function(options) {
                $.ajax({
                    url: "/admin/manager/GroupDel",
                    type: "get",
                    dataType: "json",
                    data: {id: obj.attr('data-id')},
                    async: false,
                    success: function (json) {
                        $('#my-alert').find(".am-modal-hd").html(json.msg);
                        $('#my-alert').modal();
                        if (json.status == 1) {
                            obj.parents('tr').remove();
                        }
                    },
                    error: function () {
                        console.log("AJAX fail");
                    }
                });

            },
            // closeOnConfirm: false,
            onCancel: function() {
            }
        });

    });

    //角色删除
    $("a[data-ajax='Manager_User_Del']").click(function () {
        $('#my-confirm').find(".am-modal-hd").html('确认删除这个用户么？');
        $('#my-confirm').find(".am-modal-bd").html('此操作不可恢复，请谨慎操作！').show();
        obj = $(this);
        $('#my-confirm').modal({
            relatedTarget: this,
            onConfirm: function(options) {
                $.ajax({
                    url: "/admin/manager/UserDel",
                    type: "get",
                    dataType: "json",
                    data: {id: obj.attr('data-id')},
                    async: false,
                    success: function (json) {
                        $('#my-alert').find(".am-modal-hd").html(json.msg);
                        $('#my-alert').modal();
                        if (json.status == 1) {
                            obj.parents('tr').remove();
                        }
                    },
                    error: function () {
                        console.log("AJAX fail");
                    }
                });

            },
            // closeOnConfirm: false,
            onCancel: function() {
            }
        });

    });

});