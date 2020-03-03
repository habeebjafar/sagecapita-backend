<img src="https://sagecapita.com/assets/sagecapita.png" style="margin: 0 auto 1em; display:block;" />
Hello <i>{{ucfirst($joinus->fullName)}}</i>,
<p>This is letting you know we've received your request.</p>
<p>We review and respond to requests in a rolling basis.</p>
 
<p><u>Your request:</u></p>
 
<div>
<p><b>Full Name:</b>&nbsp;{{ $joinus->fullName }}</p>
<p><b>Email:</b>&nbsp;{{ $joinus->email }}</p>
<p><b>Phone:</b>&nbsp;{{ $joinus->phone }}</p>
<p><b>Role:</b>&nbsp;{{ $joinus->role }}</p>
<p><b>Country:</b>&nbsp;{{ $joinus->country }}</p>
<p><b>Language:</b>&nbsp;{{ $joinus->language }}</p>
<p><b>Message:</b>&nbsp;{{ $joinus->message }}</p>
</div>
 
Thank You,
<br/>
<i>Sagecapita</i>