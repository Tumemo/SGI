import { Link } from "react-router-dom"

function NavBar(){
    return(
        <nav className="bg-red-500 p-5 w-100 fixed bottom-0">
            <ul className="flex justify-around items-center">
                <li>
                    <Link to={"/home"}>
                        <img src="/icons/house-icon.svg" alt="Home" />
                    </Link>
                </li>
                <li>
                    <img className="invert w-8" src="/icons/person-icon.svg" alt="Turma" />
                </li>
                <li>
                    <img src="/icons/trophy-icon.svg" alt="Ranking" />
                </li>
                <li>
                    <Link to={"/"}>
                        <img src="/icons/logout-icon.svg" alt="Sair" />
                    </Link>
                </li>
            </ul>
        </nav>
    )
}

export default NavBar