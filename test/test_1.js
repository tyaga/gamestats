db.stats_log.group({
	keyf: function(doc) {
		var date = new Date(doc.Timestamp);
		var dateKey = date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate() + "";
		return {user_id: doc.user_id, date:dateKey};
	},
	cond: { Timestamp: { $gt: new Date( '01/01/2012' ) } },

	reduce: function ( curr, result ) {
		result.count++;
	},

	initial: { count: 0 }/*,

	finalize: function(result) {

	}*/
});
