<div class="card">
    <div class="card-body">
        <div id="thermometer">
            <div class="track">
                <div class="goal">
                    <div class="amount" id="goalAmount">700</div>
                </div>
                <div class="progress">
                    <div style="display: block;" class="amount" id="progessAmount">300</div>
                </div>
                <div class="bulb">
                    <div class="inner-bulb"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@push("css")
    <style>
        #thermometer .goal {
            position: absolute;
            right: 0;
            top: 0;
        }

        #thermometer .amount {
            border-top: 1px solid #006600;
            color: black;
            display: inline-block;
            font-family: Trebuchet MS;
            font-weight: bold;
            padding: 0 75px 0 0;
        }

        #thermometer .progress .amount {
            border-top: 1px solid #006600;
            color: #006600;
            left: 0;
            padding: 0 0 0 75px;
            position: absolute;
        }

        #thermometer {
            border-radius: 12px;
            height: 300px;
            margin-top: 140px;
            position: relative;
        }

        #thermometer .track {
            background: #e5e5e5 none repeat scroll 0 0;
            border-radius: 8px;
            height: 280px;
            left: 10px;
            margin: 0 auto;
            position: relative;
            top: 10px;
            width: 30px;
        }

        #thermometer .progress {
            background: green none repeat scroll 0 0;
            border-radius: 23px 23px 0 0;
            bottom: 0;
            height: 0;
            left: 5px;
            margin-bottom: 0 !important;
            position: absolute;
            width: 69%;
            z-index: 100;
        }

        #thermometer .goal {
            position: absolute;
            right: 0;
            top: 0;
        }

        #thermometer .amount {
            border-top: 1px solid #006600;
            color: black;
            display: inline-block;
            font-family: Trebuchet MS;
            font-weight: bold;
            padding: 0 75px 0 0;
        }

        #thermometer .progress .amount {
            border-top: 1px solid #006600;
            color: #006600;
            left: 0;
            padding: 0 0 0 75px;
            position: absolute;
        }

        .bulb {
            background: #e5e5e5 none repeat scroll 0 0;
            border-radius: 50%;
            bottom: -42px;
            display: block;
            height: 40px;
            left: -14px;
            padding: 10px;
            position: absolute;
            width: 43px;
        }

        .inner-bulb {
            background-color: green;
            border-radius: 50%;
            height: 40px;
            left: 0;
            position: relative;
            top: 0;
            width: 40px;
        }
    </style>
@endpush

@push("js")
    <script>
        // JavaScript Document
        function thermometer(goalAmount, progressAmount, animate) {
            "use strict";
            var $thermo = $("#thermometer"),
                $progress = $(".progress", $thermo),
                $goal = $(".goal", $thermo),
                percentageAmount;

            goalAmount = goalAmount || parseFloat($goal.text()),
                progressAmount = progressAmount || parseFloat($progress.text()),
                percentageAmount = Math.min(Math.round(progressAmount / goalAmount * 1000) / 10, 100); //make sure we have 1 decimal point

            $goal.find(".amount").text();
            $progress.find(".amount").text();

            $progress.find(".amount").hide();
            if (animate !== false) {
                $progress.animate({
                    "height": percentageAmount + "%"
                }, 1200, function () {
                    $(this).find(".amount").fadeIn(200);
                });
            } else {
                $progress.css({
                    "height": percentageAmount + "%"
                });
                $progress.find(".amount").fadeIn(200);
            }
        }

        $(document).ready(function () {

            thermometer();

        });
    </script>
@endpush
