$.ajax({
    type: "GET",
    url: "apis/CollegeList/readCollege.php",
    success: function (data) {

        var temp = '';
        var dataArray = JSON.parse(data).result;
        console.log(dataArray);
        display_college(dataArray);

    },
    error: function (data) {
        console.log(data);
    }
});