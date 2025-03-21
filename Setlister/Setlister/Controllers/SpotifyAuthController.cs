using Microsoft.AspNetCore.Mvc;
using System;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.Extensions.Hosting; // Add this namespace to access IWebHostEnvironment
using System.Text.Json.Serialization; // Add this namespace for JsonPropertyName

namespace Setlister.Controllers
{
    public class SpotifyAuthController : Controller
    {
        private readonly string clientId = "d3bf09ec2ddc49d0b108c94cca30c6cc";
        private readonly string clientSecret = "2a113431534a4a1ebe38f5b82efb61a3";
        private readonly string redirectUri = "https://localhost:7028/callback"; // Must match Spotify Developer settings
        private readonly string scopes = "user-read-email user-read-private playlist-read-private playlist-modify-public playlist-modify-private user-library-modify user-library-read"; // Add required scopes
        private readonly HttpClient _httpClient;
        private readonly IWebHostEnvironment _env; // Inject IWebHostEnvironment

        // Inject HttpClient and IWebHostEnvironment via constructor
        public SpotifyAuthController(HttpClient httpClient, IWebHostEnvironment env)
        {
            _httpClient = httpClient;
            _env = env; // Assign IWebHostEnvironment
        }

        // 🔹 Step 1: Redirect User to Spotify Login
        [HttpGet("spotify/login")]
        public IActionResult Login()
        {
            string spotifyAuthUrl = $"https://accounts.spotify.com/authorize?client_id={clientId}&response_type=code&redirect_uri={Uri.EscapeDataString(redirectUri)}&scope={Uri.EscapeDataString(scopes)}";
            Console.WriteLine("Spotify Login URL: " + spotifyAuthUrl); // Log URL for debugging
            return Json(new { url = spotifyAuthUrl }); // Ensure the URL is sent back as a JSON response
        }

        // 🔹 Step 2: Handle Callback and Store Authorization Code
        [HttpGet("callback")]
        public async Task<IActionResult> Callback(string code)
        {
            if (string.IsNullOrEmpty(code))
            {
                Console.WriteLine("Error: Authorization code is missing.");
                return BadRequest("Authorization code missing.");
            }

            Console.WriteLine($"Received Authorization Code: {code}");

            // 🔹 Step 3: Exchange Authorization Code for Access Token
            var requestBody = new StringContent(
                $"grant_type=authorization_code&code={code}&redirect_uri={redirectUri}&client_id={clientId}&client_secret={clientSecret}",
                Encoding.UTF8,
                "application/x-www-form-urlencoded"
            );

            // Debugging: Log the request being sent
            Console.WriteLine("Sending request to Spotify token API...");
            Console.WriteLine($"Request Body: {requestBody}");

            var response = await _httpClient.PostAsync("https://accounts.spotify.com/api/token", requestBody);
            var responseBody = await response.Content.ReadAsStringAsync();

            // Debugging: Log the response status code and body
            Console.WriteLine($"Spotify Response Status: {response.StatusCode}");
            Console.WriteLine($"Response Body: {responseBody}");

            if (!response.IsSuccessStatusCode)
            {
                Console.WriteLine("Error: Failed to exchange authorization code.");
                return BadRequest($"Failed to exchange authorization code. Status Code: {response.StatusCode}");
            }

            try
            {
                var tokenData = JsonSerializer.Deserialize<SpotifyTokenResponse>(responseBody);
                var accessToken = tokenData?.AccessToken;

                // Debugging: Log the access token if available
                Console.WriteLine($"Access Token: {accessToken}");

                // Ensure accessToken is not null before proceeding
                if (string.IsNullOrEmpty(accessToken))
                {
                    Console.WriteLine("Error: Access token is missing from response.");
                    return BadRequest("Access token is missing from response.");
                }

                // 🔹 Step 4: Store Access Token in Secure Cookie
                Console.WriteLine("Storing Access Token in cookie...");
                Response.Cookies.Append("spotify_access_token", accessToken, new Microsoft.AspNetCore.Http.CookieOptions
                {
                    HttpOnly = false,
                    Secure = false, // Set Secure flag only in production (false for dev)
                    SameSite = SameSiteMode.Lax,
                    Expires = DateTime.UtcNow.AddMinutes(60) // Adjust expiration as needed
                });

                Console.WriteLine("Redirecting to Dashboard...");
                return Redirect("/dashboard");
            }
            catch (JsonException ex)
            {
                Console.WriteLine($"Error deserializing response: {ex.Message}");
                return BadRequest("Error deserializing Spotify token response.");
            }
        }
    }

    // Model for parsing Spotify's response
    public class SpotifyTokenResponse
    {
        [JsonPropertyName("access_token")]
        public string AccessToken { get; set; }

        [JsonPropertyName("token_type")]
        public string TokenType { get; set; }

        [JsonPropertyName("expires_in")]
        public int ExpiresIn { get; set; }

        [JsonPropertyName("refresh_token")]
        public string RefreshToken { get; set; }

        [JsonPropertyName("scope")]
        public string Scope { get; set; }
    }
}