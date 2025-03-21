using Microsoft.AspNetCore.Mvc;

namespace Setlister.Controllers
{
    public class HomeController : Controller
    {
        public IActionResult Dashboard()
        {
            return View();
        }
    }
}