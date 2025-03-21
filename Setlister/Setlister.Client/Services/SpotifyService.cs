using System.Text.Json;

namespace Setlister.Client.Services
{
    public class SpotifyService
    {
        private readonly HttpClient _httpClient;

        public SpotifyService(HttpClient httpClient)
        {
            _httpClient = httpClient;
        }

        public async Task<string> GetUserDisplayName(string accessToken)
        {
            var request = new HttpRequestMessage(HttpMethod.Get, "https://api.spotify.com/v1/me");
            request.Headers.Authorization = new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", accessToken);
            var response = await _httpClient.SendAsync(request);

            if (response.IsSuccessStatusCode)
            {
                var content = await response.Content.ReadAsStringAsync();
                var user = JsonSerializer.Deserialize<SpotifyUser>(content);
                return user?.DisplayName;
            }

            return null;
        }
    }

    public class SpotifyUser
    {
        public string DisplayName { get; set; }
    }
}