function success(data = null, message = 'OK') {
  return { status: 'success', message, data };
}

function error(message = 'Error', details = null) {
  return { status: 'error', message, details };
}

function paginate(total, page = 1, limit = 20) {
  const pages = Math.ceil(total / limit);
  return { total, page, limit, pages };
}

module.exports = { success, error, paginate };