<img src="https://sagecapita.com/assets/sagecapita.png" style="margin: 0 auto 1em; display:block;" />
Hello <i>{{ucfirst($mediarequest->fullName)}}</i>,
<p>This is letting you know we've received your request.</p>
<p>We would review your request and provide you with the press release requested.</p>
 
<p><u>Your request:</u></p>
 
<div>
    <p><b>Full Name:</b>&nbsp;{{ $mediarequest->fullName }}</p>
    <p><b>Email:</b>&nbsp;{{ $mediarequest->email }}</p>
    <p><b>Headline:</b>&nbsp;{{ $mediarequest->headline }}</p>
    <p><b>Country:</b>&nbsp;{{ $mediarequest->country }}</p>
</div>
 
Thank You,
<br/>
<i>Sagecapita</i>