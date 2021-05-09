window.addEventListener("loaded", () => {
  render();
  $(() => {
    $('[data-toggle="tooltip"]').tooltip();
  });
});

function render() {
  $("*[foreach]").each((i, e) => {
    $(e).css("display", "none");
  });
  Promise.all([getFormations(), getColloquia()]).then((data) => {
    store["formations"] = data[0];
    store["colloquia"] = data[1];
    foreachGenerator();
    $("*[foreach]").each((i, e) => {
      $(e).css("display", "block");
    });
  });
}

function deleteEvent(elem, type) {
  let id = $(elem).find(".edit-id").text();
  $(elem)
    .parent()
    .find("button")
    .each((i, e) => {
      $(e).attr("disabled", "true");
    });
  $.post(
    "?action=deleteEvent",
    { type, id },
    (response) => {
      if (response.status === 200) {
        window.location.reload();
      } else {
        alert("Impossible de supprimer cet événement");
      }
    },
    "json"
  );
}

function foreachGenerator() {
  $("*[foreach]").each((index, item) => {
    let variable = $(item).attr("foreach");
    let html = $(item).get();
    let parent = $(item).parent();

    if (variable in store) {
      variable = store[variable];
      variable.forEach((elem, index) => {
        let toInsert = $(html).clone();
        $(toInsert)
          .find("*[foreach-value]")
          .each((index, placeToInsertValue) => {
            let valueToInsert = $(placeToInsertValue).attr("foreach-value");
            if (typeof elem == "object") {
              if (valueToInsert in elem) {
                $(placeToInsertValue).text(elem[valueToInsert]);
              }
            } else if (typeof elem == "string") {
              $(placeToInsertValue).text(elem);
            }
          });
        $(parent).append(toInsert);
      });
    }
    $(html).remove();
  });
}

function addEvent(type) {
  window.location.href =
    window.location.href.split("?action=")[0] +
    "?action=editEvent&type=" +
    type;
}

function editFormation(elem) {
  let formationId = $(elem).find(".edit-id").text();
  window.location.href =
    window.location.href.split("?action=")[0] +
    "?action=editEvent&type=formation&id=" +
    formationId;
}

function editColloquium(elem) {
  let colloquiumId = $(elem).find(".edit-id").text();
  window.location.href =
    window.location.href.split("?action=")[0] +
    "?action=editEvent&type=colloquium&id=" +
    colloquiumId;
}

function getFormations() {
  return new Promise((resolve, reject) => {
    $.get(
      "?action=getFormations",
      (response) => {
        if (response.status === 200) {
          resolve(response.formations);
        } else {
          reject();
        }
      },
      "json"
    );
  });
}

function getColloquia() {
  return new Promise((resolve, reject) => {
    $.get(
      "?action=getColloquia",
      (response) => {
        if (response.status === 200) {
          resolve(response.colloquia);
        } else {
          reject();
        }
      },
      "json"
    );
  });
}
