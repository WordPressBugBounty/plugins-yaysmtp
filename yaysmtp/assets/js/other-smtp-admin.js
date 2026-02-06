(function($) {
  $(document).ready(function() {
    var yaysmtp_startTime_picker = moment().subtract(6, "days");
    var yaysmtp_endTime_picker = moment();
    var yay_smtp_char_obj = "";

    var yaysmtp_export_mail_log_startTime_picker = moment();
    var yaysmtp_export_mail_log_endTime_picker = moment();

    function yaysmtp_chart(fromDate, toDate) {
      if (yay_smtp_char_obj instanceof Chart) {
        yay_smtp_char_obj.destroy();
      }
      $.ajax({
        url: yaySmtpWpOtherData.YAY_ADMIN_AJAX,
        type: "POST",
        data: {
          action: "yaysmtp_overview_chart",
          nonce: yaySmtpWpOtherData.ajaxNonce,
          params: {
            from: fromDate.format("YYYY-MM-DD"),
            to: toDate.format("YYYY-MM-DD")
          }
        },
        beforeSend: function() {
          yaySMTPspinner("yaysmtp-analytics-email-wrap", true);
        },
        success: function(result) {
          if (result.success) {
            let data = result.data.data;
            const yaysmtp_labels = data.labels;
            const yaysmtp_datasets = data.datasets;
            const yaysmtp_data = {
              labels: yaysmtp_labels,
              datasets: yaysmtp_datasets
            };
            const yaysmtpConfig = {
              type: "line",
              data: yaysmtp_data,
              options: {
                responsive: true,
                ticks: {
                  precision: 0
                },
                plugins: {
                  legend: {
                    position: "top",
                    display: false
                  },
                  tooltip: {
                    multiKeyBackground: "#00000000",
                    callbacks: {
                      labelColor: function(context) {
                        return {
                          backgroundColor: context.dataset.backgroundColor
                        };
                      }
                    }
                  }
                },
                scales: {
                  y: {
                    beginAtZero: true
                  }
                }
              }
            };

            let totalMail =
              parseInt(data.successTotal) + parseInt(data.failTotal);
            if (
              $("body").find(".yaysmtp-chart-sumary .total-mail").length > 0
            ) {
              $("body")
                .find(".yaysmtp-chart-sumary .total-mail")
                .html(totalMail);
            }

            if ($("body").find(".yaysmtp-chart-sumary .sent-mail").length > 0) {
              $("body")
                .find(".yaysmtp-chart-sumary .sent-mail")
                .html(data.successTotal);
            }

            if (
              $("body").find(".yaysmtp-chart-sumary .failed-mail").length > 0
            ) {
              $("body")
                .find(".yaysmtp-chart-sumary .failed-mail")
                .html(data.failTotal);
            }

            if ($("body").find("#yaysmtpCharts").length > 0) {
              var yaysmtpctx = document
                .getElementById("yaysmtpCharts")
                .getContext("2d");

              yay_smtp_char_obj = new Chart(yaysmtpctx, yaysmtpConfig);
            }

            // Top mail List
            render_top_mail_list_html(data.topMailList);
          }
          yaySMTPspinner("yaysmtp-analytics-email-wrap", false);
        }
      });
    }

    if( 
      window.location.href === yaySmtpWpOtherData.DASHBOARD_URL 
      || window.location.href === yaySmtpWpOtherData.DASHBOARD_URL + 'index.php'
    ) {
      yaysmtp_input_daterangepicker(
        yaysmtp_startTime_picker,
        yaysmtp_endTime_picker
      );
    }

    if ($("body").find("#yaysmtp_daterangepicker").length > 0) {
      $("#yaysmtp_daterangepicker").daterangepicker(
        {
          startDate: yaysmtp_startTime_picker,
          endDate: yaysmtp_endTime_picker,
          alwaysShowCalendars: true,
          showCustomRangeLabel: false,
          autoUpdateInput: false,
          ranges: {
            "Last 7 Days": [moment().subtract(6, "days"), moment()],
            "This Month": [moment().startOf("month"), moment().endOf("month")],
            "Last 3 Months": [moment().subtract(90, "days"), moment()]
          },
          locale: {
            cancelLabel: "Reset",
            format: "YYYY-MM-DD"
          }
        },
        yaysmtp_input_daterangepicker
      );
    }

    $("body")
      .find("#yaysmtp_daterangepicker")
      .on("cancel.daterangepicker", function(ev, picker) {
        $("#yaysmtp_daterangepicker")
          .data("daterangepicker")
          .setStartDate(yaysmtp_startTime_picker.format("YYYY/MM/DD"));
        $("#yaysmtp_daterangepicker")
          .data("daterangepicker")
          .setEndDate(yaysmtp_endTime_picker.format("YYYY/MM/DD"));

        yaysmtp_input_daterangepicker(
          yaysmtp_startTime_picker,
          yaysmtp_endTime_picker
        );
      });

    if ($("body").find("#yaysmtp_daterangepicker_export_mail_logs").length > 0) {
      $("#yaysmtp_daterangepicker_export_mail_logs").daterangepicker(
        {
          startDate: yaysmtp_export_mail_log_startTime_picker,
          endDate: yaysmtp_export_mail_log_endTime_picker,
          alwaysShowCalendars: true,
          showCustomRangeLabel: false,
          autoUpdateInput: false,
          ranges: {
            Today: [moment(), moment()],
            // "Last 7 Days": [moment().subtract(6, "days"), moment()],
            "This Month": [moment().startOf("month"), moment().endOf("month")],
            "Last Month": [
              moment()
                .subtract(1, "month")
                .startOf("month"),
              moment()
                .subtract(1, "month")
                .endOf("month")
            ],
            // "Last 6 Months": [moment().subtract(180, "days"), moment()],
            "This Year": [moment().startOf("year"), moment()]
          },
          locale: {
            cancelLabel: "Cancel",
            format: "YYYY-MM-DD"
          }
        },
        yaysmtp_input_export_email_log_daterangepicker
      );
    }
    
    $("body")
      .find("#yaysmtp_daterangepicker_export_mail_logs")
      .on("cancel.daterangepicker", function(ev, picker) {
        let startDate = moment();
        let endDate = moment();

        $("#yaysmtp_daterangepicker_export_mail_logs")
          .data("daterangepicker")
          .setStartDate(startDate.format("YYYY/MM/DD"));
        $("#yaysmtp_daterangepicker_export_mail_logs")
          .data("daterangepicker")
          .setEndDate(endDate.format("YYYY/MM/DD"));

          yaysmtp_input_export_email_log_daterangepicker("", "");
      });

    function yaysmtp_input_daterangepicker(start, end) {
      $("#yaysmtp_daterangepicker").val(
        start.format("YYYY/MM/DD") + " - " + end.format("YYYY/MM/DD")
      );

      yaysmtp_chart(start, end);
    }

    function yaysmtp_input_export_email_log_daterangepicker(start, end) {
      if( start && end ) {
        $("#yaysmtp_daterangepicker_export_mail_logs").val(
          start.format("YYYY/MM/DD") + " - " + end.format("YYYY/MM/DD")
        );
      } else {
        $("#yaysmtp_daterangepicker_export_mail_logs").val("");
      }
     
      yaysmtp_export_mail_log_startTime_picker = start ? start : moment();
      yaysmtp_export_mail_log_endTime_picker   = end ? end : moment();
    }

    function render_top_mail_list_html(topMailList) {
      $(
        ".yaysmtp-analytics-email-wrap .top-mail-table-wrap .top-mail-body"
      ).html("");

      let html = "";
      if (topMailList.length > 0) {
        topMailList.forEach(function(item) {
          html += "<tr>";
          html += '<td class="table-item">' + item.title + "</td>";
          html +=
            '<td class="table-item">' +
            (parseInt(item.sent) + parseInt(item.failed)) +
            "</td>";
          html += '<td class="table-item">' + item.sent + "</td>";
          html += '<td class="table-item">' + item.failed + "</td>";
          html += "</tr>";
        });
      }
      if (html != "") {
        $(
          ".yaysmtp-analytics-email-wrap .top-mail-table-wrap .top-mail-body"
        ).html(html);
        $(
          ".yaysmtp-analytics-email-wrap .top-mail-table-wrap .top-mail-table"
        ).show();
        $(
          ".yaysmtp-analytics-email-wrap .top-mail-table-wrap .top-mail-table-empty"
        ).hide();
      } else {
        $(
          ".yaysmtp-analytics-email-wrap .top-mail-table-wrap .top-mail-table"
        ).hide();
        $(
          ".yaysmtp-analytics-email-wrap .top-mail-table-wrap .top-mail-table-empty"
        ).show();
      }
    }

  });
})(window.jQuery);

function yaySMTPspinner(containerClass, isShow) {
  let spinnerHtml = '<div class="yay-smtp-spinner">';
  spinnerHtml +=
    '<svg class="woocommerce-spinner" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">';
  spinnerHtml +=
    '<circle class="woocommerce-spinner__circle" fill="none" stroke-width="5" stroke-linecap="round" cx="50" cy="50" r="30"></circle>';
  spinnerHtml += "/<svg>";
  spinnerHtml += "</div>";
  if (isShow) {
    jQuery("." + containerClass).append(spinnerHtml);
  } else {
    jQuery(".yay-smtp-spinner").remove();
  }
}

function yaySMTPNotification(messages, containerClass, success) {
  let icon =
    '<div class="icon"><svg viewBox="64 64 896 896" data-icon="check-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" class=""><path d="M699 353h-46.9c-10.2 0-19.9 4.9-25.9 13.3L469 584.3l-71.2-98.8c-6-8.3-15.6-13.3-25.9-13.3H325c-6.5 0-10.3 7.4-6.5 12.7l124.6 172.8a31.8 31.8 0 0 0 51.7 0l210.6-292c3.9-5.3.1-12.7-6.4-12.7z"></path><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path></svg></div>';

  if (!success) {
    icon =
      '<div class="icon"><svg viewBox="64 64 896 896" data-icon="close-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" class=""><path d="M685.4 354.8c0-4.4-3.6-8-8-8l-66 .3L512 465.6l-99.3-118.4-66.1-.3c-4.4 0-8 3.5-8 8 0 1.9.7 3.7 1.9 5.2l130.1 155L340.5 670a8.32 8.32 0 0 0-1.9 5.2c0 4.4 3.6 8 8 8l66.1-.3L512 564.4l99.3 118.4 66 .3c4.4 0 8-3.5 8-8 0-1.9-.7-3.7-1.9-5.2L553.5 515l130.1-155c1.2-1.4 1.8-3.3 1.8-5.2z"></path><path d="M512 65C264.6 65 64 265.6 64 513s200.6 448 448 448 448-200.6 448-448S759.4 65 512 65zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path></svg></div>';
  }

  let notifyHtml =
    '<div class="yay-smtp-notification"><div class="yay-smtp-notification-content">' +
    icon +
    '<div class="content">' +
    messages +
    "<div>" +
    "</div></div>";

  jQuery("." + containerClass).after(notifyHtml);
  setTimeout(function() {
    jQuery(".yay-smtp-notification").addClass("NslideDown");
    jQuery(".yay-smtp-notification").remove();
  }, 1500);
}