"use strict";

//var baseUrl = "http://129.8.229.220";
var baseUrl = "http://192.168.100.84";
var workingFoodId = 0;

function searchFoods() {
    let str = document.getElementById("search").value;
    let url = baseUrl + "/api/get.php?type=search&food=" + str;
    getData(url, function(json) {
        for (const food of json) {
            console.log(food.name);
            let results = document.getElementById("autocomplete-list");
            let r = document.createElement("div");
            r.innerHTML = food.name;
            results.append(r);
        }
    });
}

function getGrouplessFoods() {
    let url = baseUrl + "/api/get.php?type=groupless";

    getData(url, function(obj) {
        let a = document.getElementById("groupless");
        a.innerHTML = "";
        console.log("groupless");

        for (let i = 0; i < obj.length; i++) {
            let d = document.createElement("div");

            if (i === 0) {
                workingFoodId = obj[i].id;
                d.style.fontWeight = 900;
                d.innerHTML = ">> " + obj[i].name;
            } else {
                d.innerHTML = obj[i].name;
            }

            a.appendChild(d);
        }
    });
}

function AssignGroup(groupid, id) {
    let url =
        baseUrl + "/api/get.php?type=assign&groupid=" + groupid + "&id=" + id;
    getData(url, function(obj) {
        console.log(obj);
        getGrouplessFoods();
        getGroups();
    });
}

function getGroups() {
    let url = baseUrl + "/api/get.php?type=GetGroups";

    getData(url, function(obj) {
        console.log(obj);
        let a = document.getElementById("groups");
        a.innerHTML = "";
        for (let i = 0; i < obj.length; i++) {
            let d = document.createElement("div");
            d.innerHTML = obj[i].name;
            d.style = "cursor: pointer";
            d.onclick = function() {
                console.log(obj[i].id);
                AssignGroup(obj[i].groupid, workingFoodId);
            };
            a.appendChild(d);
        }
    });
}

function getData(url, callback = noop) {
    let data = {};
    console.log(url);

    // Sending and receiving data in JSON format using POST method
    let xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                let json = JSON.parse(xhr.responseText);
                callback(json);
            } else {
                activateSection("error");
            }
        }
    };
    xhr.send(data);
}

getGrouplessFoods();
getGroups();
