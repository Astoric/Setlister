using Microsoft.AspNetCore.Components;
using Microsoft.AspNetCore.Components.Authorization;
using Microsoft.JSInterop;
using System.Security.Claims;
using System.Threading.Tasks;

public class AuthStateProvider : AuthenticationStateProvider
{
    private readonly IJSRuntime _jsRuntime;
    private readonly NavigationManager _navigationManager;

    public AuthStateProvider(IJSRuntime jsRuntime, NavigationManager navigationManager)
    {
        _jsRuntime = jsRuntime;
        _navigationManager = navigationManager;
    }

    public override async Task<AuthenticationState> GetAuthenticationStateAsync()
    {
        // Check if the spotify_access_token cookie exists
        var accessToken = await _jsRuntime.InvokeAsync<string>("eval", "document.cookie.split('; ').find(row => row.startsWith('spotify_access_token='))?.split('=')[1];");

        if (!string.IsNullOrEmpty(accessToken))
        {
            //  The user is authenticated
            var claims = new[] { new Claim(ClaimTypes.Name, "spotifyUser") }; // Or extract more claims from the token
            var identity = new ClaimsIdentity(claims, "spotifyAuth");
            var user = new ClaimsPrincipal(identity);
            return new AuthenticationState(user);
        }
        else
        {
            // User is not authenticated, redirect to login page
            _navigationManager.NavigateTo("/", forceLoad: true); // Use forceLoad to prevent caching issues
            return new AuthenticationState(new ClaimsPrincipal(new ClaimsIdentity())); //  Return an unauthenticated state
        }
    }
}